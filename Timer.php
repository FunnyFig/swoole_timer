<?php

namespace FunnyFig\Swoole;

use co;

require_once 'vendor/autoload.php';
use FunnyFig\Swoole\Thread;

class Timer extends Thread {
	protected const STOP = 0;

	protected $_next;
	protected $_proc;

	function __construct(callable $proc, int $start=0, $period=0)
	{
		$this->_next = $this->pacemaker($start, $period);
		$this->_proc = $proc;
		parent::__construct();
	}

	function stop()
	{
		$this->invoke(self::STOP);
	}

	protected function next()
	{
		return call_user_func($this->_next);
	}

	protected function proc()
	{
		$ms = $this->next();

		while ($this->get_cmd($ms)===false) {
			try {
				($this->_proc)();
			}
			catch (Throwable $t) {
			}

			$ms = $this->next();

			if ($ms <= 0) break;
		}
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

