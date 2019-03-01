<?php

namespace app\common\command; 
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Crontab extends Command
{
	protected function configure(){
		$this->setName('Crontab')->setDescription("计划任务 Crontab");
	}
 
	protected function execute(Input $input, Output $output){
		$output->writeln('Date Crontab job start...');
		/*** 这里写计划任务列表集 START ***/
 
		$this->test();
 
		/*** 这里写计划任务列表集 END ***/
		$output->writeln('Date Crontab job end...');
	}
 
	private function test(){
		echo "test\r\n";
	}

}
