<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/interfaces/interface.ilAudingFileRequest.php';

/**
 * Class ilAudingFileRequestImpl
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilAudingFileRequestImpl implements ilAudingFileRequest
{
	/**
	 * {@inheritdoc}
	 */
	public function handleRequest(ilAudingRequestableFile $file)
	{
		ilLoggerFactory::getRootLogger()->info('Auding: Got auding delivery request ..');

		if(!$file->exists())
		{
			ilLoggerFactory::getRootLogger()->info('Auding: Requested auding file does not exist ....');
			ilHTTP::status(404);
			return;
		}

		if(!$file->isAccessible() || !$file->isPlayable())
		{
			ilLoggerFactory::getRootLogger()->info('Auding: Requesting user has no access ...');
			ilHTTP::status(403);
			return;
		}

		require_once 'Services/FileDelivery/classes/class.ilFileDelivery.php';
		$ilFileDelivery = new ilFileDelivery($file->getFile());
		$ilFileDelivery->setCache(false);
		$ilFileDelivery->setDisposition(ilFileDelivery::DISP_INLINE);
		ilLoggerFactory::getRootLogger()->info('Auding: Deliver "' . $file->getFile() . '" file using ' . $ilFileDelivery->getDeliveryType());
		ilLoggerFactory::getRootLogger()->info('Auding: Begin streaming ....');
		$ilFileDelivery->stream();
	}
}
// auding-patch: end