<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 *
 */
class TasksService extends BaseApplicationComponent
{
	private $_taskRecordsById;
	private $_nextPendingTask;
	private $_runningTask;

	/**
	 * Creates a task to run later in the system.
	 *
	 * @param string      $type
	 * @param string|null $description
	 * @param array|null  $settings
	 * @param int|null    $parentId
	 * @throws \Exception
	 * @return TaskModel
	 */
	public function createTask($type, $description = null, $settings = array(), $parentId = null)
	{
		$task = new TaskModel();
		$task->type = $type;
		$task->description = $description;
		$task->settings = $settings;
		$task->parentId = $parentId;
		$this->saveTask($task);
		return $task;
	}

	/**
	 * Saves a task.
	 *
	 * @param TaskModel $task
	 * @param bool      $validate
	 * @return bool
	 */
	public function saveTask(TaskModel $task, $validate = true)
	{
		if ($task->isNew())
		{
			$taskRecord = new TaskRecord();
		}
		else
		{
			$taskRecord = $this->_getTaskRecordById($task->id);
		}

		$taskRecord->type        = $task->type;
		$taskRecord->status      = $task->status;
		$taskRecord->settings    = $task->settings;
		$taskRecord->description = $task->description;
		$taskRecord->totalSteps  = $task->totalSteps;
		$taskRecord->currentStep = $task->currentStep;

		if (!$task->parentId || !$task->isNew())
		{
			$success = $taskRecord->saveNode($validate);
		}
		else
		{
			$parentTaskRecord = $this->_getTaskRecordById($task->parentId);
			$success = $taskRecord->appendTo($parentTaskRecord, $validate);
		}

		if ($success)
		{
			if ($task->isNew())
			{
				$task->id = $taskRecord->id;

				if ($task->parentId)
				{
					// We'll be needing this soon
					$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
				}
			}

			return true;
		}
		else
		{
			$task->addErrors($taskRecord->getErrors());
			return false;
		}
	}

	/**
	 * Re-runs a task by a given ID.
	 *
	 * @param $taskId
	 * @return TaskModel|null
	 */
	public function rerunTaskById($taskId)
	{
		$task = $this->getTaskById($taskId);

		if ($task && $task->level == 0)
		{
			$task->currentStep = null;
			$task->totalSteps = null;
			$task->status = TaskStatus::Pending;
			$this->saveTask($task);

			// Delete any of its subtasks
			$taskRecord = $this->_getTaskRecordById($taskId);
			$subtaskRecords = $taskRecord->descendants()->findAll();

			foreach ($subtaskRecords as $subtaskRecord)
			{
				$subtaskRecord->deleteNode();
			}

			return $task;
		}
	}

	/**
	 * Runs any pending tasks.
	 */
	public function runPendingTasks()
	{
		// If we're already processing tasks, let's give it a break.
		if ($this->isTaskRunning())
		{
			Craft::log('Tasks are already running.', LogLevel::Info, true);
			return;
		}

		// It's go time.
		craft()->config->maxPowerCaptain();

		while ($task = $this->getNextPendingTask())
		{
			$this->_runningTask = $task;
			$this->runTask($task);
		}

		$this->_runningTask = null;
	}

	/**
	 * Runs a given task.
	 *
	 * @param TaskModel $task
	 * @return bool
	 */
	public function runTask(TaskModel $task)
	{
		$error = null;

		try
		{
			$taskRecord = $this->_getTaskRecordById($task->id);
			$taskType = $task->getTaskType();

			if ($taskType)
			{
				// Figure out how many total steps there are.
				$task->totalSteps = $taskType->getTotalSteps();
				$task->status = TaskStatus::Running;

				Craft::Log('Starting task '.$taskRecord->type.' that has a total of '.$task->totalSteps.' steps.', LogLevel::Info, true);

				for ($step = 0; $step < $task->totalSteps; $step++)
				{
					// Update the task
					$task->currentStep = $step+1;
					$this->saveTask($task);

					Craft::Log('Starting step '.($step+1).' of '.$task->totalSteps.' total steps.', LogLevel::Info, true);

					// Run it.
					if (($result = $taskType->runStep($step)) !== true)
					{
						// Did they give us an error to report?
						if (is_string($result))
						{
							$error = $result;
						}
						else
						{
							$error = true;
						}

						break;
					}
				}
			}
			else
			{
				$error = 'Could not find the task component type.';
			}
		}
		catch (\Exception $e)
		{
			$error = 'An exception was thrown: '.$e->getMessage();
		}

		if ($task == $this->_nextPendingTask)
		{
			// Don't run this again
			$this->_nextPendingTask = null;
		}

		if ($error === null)
		{
			Craft::log('Finished task '.$task->id.' ('.$task->type.').', LogLevel::Info, true);

			// We're done with this task, nuke it.
			$taskRecord->deleteNode();

			return true;
		}
		else
		{
			$this->fail($task, $error);
			return false;
		}
	}

	/**
	 * Sets a task's status to "error" and logs it.
	 *
	 * @param TaskModel $task
	 * @param mixed     $error
	 */
	public function fail(TaskModel $task, $error = null)
	{
		$task->status = TaskStatus::Error;
		$this->saveTask($task);

		// Log it
		$logMessage = 'Encountered an error running task '.$task->id.' ('.$task->type.')';

		if ($task->currentStep)
		{
			$logMessage .= ', step '.$task->currentStep;

			if ($task->totalSteps)
			{
				$logMessage .= ' of '.$task->totalSteps;
			}
		}

		if ($error && is_string($error))
		{
			$logMessage .= ': '.$error;
		}
		else
		{
			$logMessage .= '.';
		}

		Craft::log($logMessage, LogLevel::Error);
	}

	/**
	 * Returns a task by its ID.
	 *
	 * @param int $taskId
	 * @return TaskModel|null
	 */
	public function getTaskById($taskId)
	{
		$result = craft()->db->createCommand()
			->select('*')
			->from('tasks')
			->where('id = :id', array(':id' => $taskId))
			->queryRow();

		if ($result)
		{
			return TaskModel::populateModel($result);
		}
	}

	/**
	 * Returns all the tasks.
	 */
	public function getAllTasks()
	{
		$results = craft()->db->createCommand()
			->select('*')
			->from('tasks')
			->order('root asc, lft asc')
			->queryAll();

		return TaskModel::populateModels($results);
	}

	/**
	 * Returns the currently running task.
	 *
	 * @return TaskModel|null
	 */
	public function getRunningTask()
	{
		if (!isset($this->_runningTask))
		{
			$result = craft()->db->createCommand()
				->select('*')
				->from('tasks')
				->where(
					array('and', 'lft = 1', 'status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/),
					array(':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/)
				)
				->queryRow();

			if ($result)
			{
				$this->_runningTask = TaskModel::populateModel($result);
			}
			else
			{
				$this->_runningTask = false;
			}
		}

		if ($this->_runningTask)
		{
			return $this->_runningTask;
		}
	}

	/**
	 * Returns whether there is a task that is currently running.
	 *
	 * @return bool
	 */
	public function isTaskRunning()
	{
		// Remember that a root task could appear to be stagnant if it has sub-tasks.
		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where(
				array('and','status = :status'/*, 'dateUpdated >= :aMinuteAgo'*/),
				array(':status' => TaskStatus::Running/*, ':aMinuteAgo' => DateTimeHelper::formatTimeForDb('-1 minute')*/)
			)
			->count('id');
	}

	/**
	 * Returns whether there are any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type
	 * @return bool
	 */
	public function areTasksPending($type = null)
	{
		$conditions = array('and', 'lft = 1', 'status = :status');
		$params = array(':status' => TaskStatus::Pending);

		if ($type)
		{
			$conditions[] = 'type = :type';
			$params[':type'] = $type;
		}

		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where($conditions, $params)
			->count('id');
	}

	/**
	 * Returns any pending tasks, optionally by a given type.
	 *
	 * @param string|null $type
	 * @param int|null $limit
	 * @return array
	 */
	public function getPendingTasks($type = null, $limit = null)
	{
		$conditions = array('and', 'lft = 1', 'status = :status');
		$params = array(':status' => TaskStatus::Pending);

		if ($type)
		{
			$conditions[] = 'type = :type';
			$params[':type'] = $type;
		}

		$query = craft()->db->createCommand()
			->from('tasks')
			->where($conditions, $params);

		if ($limit)
		{
			$query->limit($limit);
		}

		$results = $query->queryAll();
		return TaskModel::populateModels($results);
	}

	/**
	 * Returns whether any tasks that have failed.
	 *
	 * @return bool
	 */
	public function haveTasksFailed()
	{
		return (bool) craft()->db->createCommand()
			->from('tasks')
			->where(array('and', 'level = 0', 'status = :status'), array(':status' => TaskStatus::Error))
			->count('id');
	}

	/**
	 * Returns the total number of active tasks.
	 *
	 * @return bool
	 */
	public function getTotalTasks()
	{
		return craft()->db->createCommand()
			->from('tasks')
			->where(
				array('and', 'lft = 1', 'status != :status'),
				array(':status' => TaskStatus::Error)
			)
			->count('id');
	}

	/**
	 * Returns the next pending task.
	 *
	 * @param string|null $type
	 * @return TaskModel|null
	 */
	public function getNextPendingTask($type = null)
	{
		// If a type was passed, we don't need to actually save it,
		// as it's probably not an actual task-running request
		if ($type)
		{
			$pendingTasks = $this->getPendingTasks($type, 1);

			if ($pendingTasks)
			{
				return $pendingTasks[0];
			}
		}
		else
		{
			if (!isset($this->_nextPendingTask))
			{
				$taskRecord = TaskRecord::model()->roots()->ordered()->findByAttributes(array(
					'status' => TaskStatus::Pending
				));

				if ($taskRecord)
				{
					$this->_taskRecordsById[$taskRecord->id] = $taskRecord;
					$this->_nextPendingTask = TaskModel::populateModel($taskRecord);
				}
				else
				{
					$this->_nextPendingTask = false;
				}
			}

			if ($this->_nextPendingTask)
			{
				return $this->_nextPendingTask;
			}
		}
	}

	/**
	 * Deletes a task by its ID.
	 *
	 * @param int $taskId
	 * @return bool
	 */
	public function deleteTaskById($taskId)
	{
		$taskRecord = $this->_getTaskRecordById($taskId);
		$success = $taskRecord->deleteNode();
		unset($this->_taskRecordsById[$taskId]);
		return $success;
	}

	/**
	 * Returns a task by its ID.
	 *
	 * @access private
	 * @param int $taskId
	 * @return TaskRecord|null
	 */
	private function _getTaskRecordById($taskId)
	{
		if (!isset($this->_taskRecordsById[$taskId]))
		{
			$this->_taskRecordsById[$taskId] = TaskRecord::model()->findById($taskId);

			if (!$this->_taskRecordsById[$taskId])
			{
				$this->_taskRecordsById[$taskId] = false;
			}
		}

		if ($this->_taskRecordsById[$taskId])
		{
			return $this->_taskRecordsById[$taskId];
		}
	}
}
