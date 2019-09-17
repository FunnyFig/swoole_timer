<?php

require_once 'vendor/autoload.php';

use FunnyFig\Swoole\Thread;

class Timer extends Thread {
	protected const STOP = 0;

	protected $_next;

	function __construct(callable $proc, int $start=0, $period=0)
	{
		$this->_next = $this->pacemaker($start, $period);
		parent::__construct($proc);
	}

	function stop()
	{
		$this->invoke(self::STOP);
	}

	protected function next()
	{
		return call_user_func($this->_next);
	}

	protected function proc(callable $proc)
	{
		$ms = $this->next();

		do {
			$cmd = $this->get_cmd($ms);
			if ($cmd !== false) {
				break;
			}

			try {
				$proc();
			}
			catch (Throwable $t) {
			}
			$ms = $this->next();

		} while ($ms>0);
	}

	protected function pacemaker($start, $period)
	{
		return function () use($start, $period)
		{
			if ($start<1) $start = 1;
			$this->_next = function () use($period) {
				$rv = is_callable($period)? $period() : $period;
				if ($rv < 0) $rv = 0;
				return $rv;
			};
			return $start;
		};
	}
}

if (!debug_backtrace()) {

$t = new Timer(function () {
	echo "proc\n";
}, 0, 100);

go(function ($t) {
	co::sleep(3);
	$t->stop();
}, $t);

}

