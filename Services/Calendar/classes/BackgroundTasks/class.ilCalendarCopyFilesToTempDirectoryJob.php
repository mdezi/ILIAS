<?php

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Types\ListType;
use ILIAS\BackgroundTasks\Types\TupleType;

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilCalendarCopyFilesToTempDirectoryJob extends AbstractJob
{
	/**
	 * @var ilLogger
	 */
	private $logger = null;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->logger = $GLOBALS['DIC']->logger()->cal();
	}






	/**
	 */
	public function getInputTypes()
	{
		return 
		[
			new SingleType(
				ilCalendarCopyDefinition::class
			)
		];
	}

	/**
	 * @todo output should be file type
	 * @return SingleType
	 */
	public function getOutputType()
	{
		return new SingleType(StringValue::class);
	}

	public function isStateless()
	{
		return true;
	}

	/**
	 * run the job
	 * @param Value $input
	 * @param Observer $observer
	 */
	public function run(array $input, Observer $observer)
	{
		$this->logger->info('Called copy files job');
		
		// create temp directory 
		$tmpdir = $this->createUniqueTempDirectory();
		
		// copy files from source to temp directory
		$this->copyFiles($tmpdir, $input[0]);
		
		// zip 
		
		// return zip file name
		$this->logger->debug('Returning new tempdirectory: ' . $tmpdir);
		
		$out = new StringValue();
		$out->setValue($tmpdir);
		return $out;
	}
	
	/**
	 * @todo refactor to new file system access
	 * Create unique temp directory
	 * @return string absolute path to new temp directory
	 */
	protected function createUniqueTempDirectory()
	{
		$tmpdir = ilUtil::ilTempnam();
		ilUtil::makeDirParents($tmpdir);
		$this->logger->info('New temp directory: ' . $tmpdir);
		return $tmpdir;
	}
	
	/**
	 * Copy files
	 * @param string $tmpdir
	 * @param ilCalendarCopyDefinition $definition
	 */
	protected function copyFiles($tmpdir, ilCalendarCopyDefinition $definition)
	{
		foreach($definition->getCopyDefinitions() as $copy_task)
		{
			if(!file_exists($copy_task[ilCalendarCopyDefinition::COPY_SOURCE_DIR]))
			{
				$this->logger->notice('Cannot find file: ' . $copy_task[ilCalendarCopyDefinition::COPY_SOURCE_DIR]);
				continue;
			}
			$this->logger->debug('Creating directory: '. $tmpdir.'/'.dirname($copy_task[ilCalendarCopyDefinition::COPY_TARGET_DIR]));
			ilUtil::makeDirParents(
				$tmpdir.'/'.dirname($copy_task[ilCalendarCopyDefinition::COPY_TARGET_DIR])
			);
			
			$this->logger->debug(
				'Copying from: ' . 
				$copy_task[ilCalendarCopyDefinition::COPY_SOURCE_DIR].
				' to '.
				$tmpdir.'/'.$copy_task[ilCalendarCopyDefinition::COPY_TARGET_DIR]
			);

			copy(
				$copy_task[ilCalendarCopyDefinition::COPY_SOURCE_DIR],
				$tmpdir.'/'.$copy_task[ilCalendarCopyDefinition::COPY_TARGET_DIR]
			);
		}
		return;
	}

}
?>