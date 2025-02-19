<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    public const NICK = null;

    /** @var int  */
    protected $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    /** @var int */
    protected $interval = 60; // in seconds

    /** @var \Magento\Framework\Event\Manager  */
    protected $eventManager;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory  */
    protected $parentFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resource;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    protected $lockItemManager;
    /** @var \Ess\M2ePro\Model\Cron\OperationHistory */
    protected $operationHistory;
    /** @var \Ess\M2ePro\Model\Cron\OperationHistory */
    protected $parentOperationHistory;
    /** @var \Ess\M2ePro\Model\Cron\Task\Repository */
    protected $taskRepo;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;
    /** @var \Ess\M2ePro\Model\Cron\Manager */
    private $cronManager;
    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->eventManager = $eventManager;
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resource = $resource;
        $this->helperData = $helperData;
        $this->taskRepo = $taskRepo;
        $this->cronManager = $cronManager;
    }

    //########################################

    public function process()
    {
        $this->initialize();
        $this->cronManager->setLastAccess($this->getConfigGroup());

        if (!$this->isPossibleToRun()) {
            return;
        }

        $this->cronManager->setLastRun($this->getConfigGroup());
        $this->beforeStart();

        try {
            $this->eventManager->dispatch(
                \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_START_EVENT_NAME,
                ['progress_nick' => $this->getNick()]
            );

            $this->performActions();

            $this->eventManager->dispatch(
                \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_STOP_EVENT_NAME,
                ['progress_nick' => $this->getNick()]
            );
        } catch (\Exception $exception) {
            $this->processTaskException($exception);
        }

        $this->afterEnd();
    }

    // ---------------------------------------

    abstract protected function performActions();

    //########################################

    protected function getNick()
    {
        $nick = static::NICK;
        if (empty($nick)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Task NICK is not defined.');
        }

        return $nick;
    }

    // ---------------------------------------

    public function setInitiator($value)
    {
        $this->initiator = (int)$value;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager
     *
     * @return $this
     */
    public function setLockItemManager(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->lockItemManager = $lockItemManager;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    public function getLockItemManager()
    {
        return $this->lockItemManager;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Cron\OperationHistory $object
     *
     * @return $this
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\Cron\OperationHistory $object)
    {
        $this->parentOperationHistory = $object;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    public function getParentOperationHistory()
    {
        return $this->parentOperationHistory;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
        $synchronizationLog = $this->activeRecordFactory->getObject('Synchronization_Log');
        $synchronizationLog->setInitiator($this->initiator);
        $synchronizationLog->setOperationHistoryId($this->getOperationHistory()->getId());

        return $synchronizationLog;
    }

    //########################################

    /**
     * @return bool
     */
    public function isPossibleToRun()
    {
        if ($this->getInitiator() === \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return true;
        }

        if (!$this->isModeEnabled()) {
            return false;
        }

        if ($this->isComponentDisabled()) {
            return false;
        }

        $currentTimeStamp = $this->helperData->getCurrentGmtDate(true);

        $startFrom = $this->getConfigValue('start_from');
        $startFrom = !empty($startFrom) ?
            (int)$this->helperData->createGmtDateTime($startFrom)->format('U') : $currentTimeStamp;

        return $startFrom <= $currentTimeStamp && $this->isIntervalExceeded();
    }

    //########################################

    protected function initialize()
    {
        $this->getHelper('Module_Exception')->setFatalErrorHandler();
        $this->activeRecordFactory->getObject('Synchronization_Log')->setFatalErrorHandler();
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        $parentId = $this->getParentOperationHistory()
            ? $this->getParentOperationHistory()->getObject()->getId() : null;
        $nick = str_replace("/", "_", $this->getNick());
        $this->getOperationHistory()->start('cron_task_' . $nick, $parentId, $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected function getOperationHistory()
    {
        if ($this->operationHistory !== null) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('Cron_OperationHistory');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function isModeEnabled()
    {
        $mode = $this->getConfigValue('mode');

        if ($mode !== null) {
            return (bool)$mode;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isIntervalExceeded()
    {
        $lastRun = $this->cronManager->getLastRun($this->getConfigGroup());

        if ($lastRun === null) {
            return true;
        }

        $currentTimeStamp = $this->helperData->getCurrentGmtDate(true);

        $lastRunTimestamp = (int)$lastRun->format('U');

        return $currentTimeStamp > $lastRunTimestamp + $this->getInterval();
    }

    public function getInterval()
    {
        $interval = $this->getConfigValue('interval');

        return $interval === null ? $this->interval : (int)$interval;
    }

    public function isComponentDisabled()
    {
        if (count($this->getHelper('Component')->getEnabledComponents()) === 0) {
            return true;
        }

        return in_array(
            $this->taskRepo->getTaskComponent($this->getNick()),
            $this->getHelper('Component')->getDisabledComponents(),
            true
        );
    }

    //########################################

    protected function processTaskException(\Exception $exception)
    {
        $this->getOperationHistory()->addContentData(
            'exceptions',
            [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );

        $this->getSynchronizationLog()->addMessageFromException($exception);

        $this->getHelper('Module_Exception')->process($exception);
    }

    protected function processTaskAccountException($message, $file, $line, $trace = null)
    {
        $this->getOperationHistory()->addContentData(
            'exceptions',
            [
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'trace' => $trace,
            ]
        );

        $this->getSynchronizationLog()->addMessage(
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    //########################################

    protected function getConfig()
    {
        return $this->getHelper('Module')->getConfig();
    }

    protected function getConfigGroup(): string
    {
        return '/cron/task/' . $this->getNick() . '/';
    }

    // ---------------------------------------

    protected function setConfigValue($key, $value)
    {
        return $this->getConfig()->setGroupValue($this->getConfigGroup(), $key, $value);
    }

    protected function getConfigValue($key)
    {
        return $this->getConfig()->getGroupValue($this->getConfigGroup(), $key);
    }

    //########################################
}
