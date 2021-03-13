<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Export.tag
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

/**
 * Joomla! Job One plugin
 *
 * An example for a job plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgExportTag extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    ApplicationCms
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    DatabaseDriver
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * URL to send the data.
	 *
	 * @var    string
	 * @since  3.5
	 */
	protected $serverUrl = '';

	/**
	 * First step to enter the sampledata. Content.
	 *
	 * @return  array or void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxTag()
	{
		//$app = JFactory::getApplication();
		$id  = $this->app->input->get('id');
		$ext  = $this->app->input->get('ext');
		$domain = $this->params->get('url', 'http://localhost/sit');
		$key = $this->params->get('key');
		$endpoint = '/api/index.php/v1/tags';
		$this->serverUrl = $domain . $endpoint;
		var_dump($this->serverUrl);

		//exit();
		// Get an instance of the generic articles model
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/models/', 'TagsModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables/');
		$model = JModelLegacy::getInstance('Tag', 'TagsModel', array('ignore_request' => true));

		$item = $model->getItem($id);

		//var_dump($item->params);
		//$registry = new Registry();
		//$item->params = new Registry($item->params);
		//$registry->set('workflow_id','1');
		
		//$item->params->merge($registry);
		unset($item->created_user_id);
		//var_dump($item);

		//exit();
		//$item->catid= 2;
		//$item->created_by=644;
		$content = json_encode($item);

		$response = $this->sendData2($content);

		if ($response->code !== 200)
		{
			$data = json_decode($response->body);

			$this->app->redirect(JRoute::_('index.php?option=com_tags&view=tag&layout=edit&id=' . $id, false), $response->code . ' - ' . $data->errors[0]->title, 'error');
		}

		$this->app->redirect(JRoute::_('index.php?option=com_tags&layout=edit&id=' . $id), 'Exported', 'success');

	}

	/**
	 * Send the stats to the stats server
	 *
	 * @return  boolean
	 *
	 * @since   3.5
	 *
	 * @throws  RuntimeException  If there is an error sending the data.
	 */
	private function sendData($content)
	{
		$options = new Registry;
		$options->set('Content-Type', 'application/json');
		$options->set('Authorization', 'API Key');
		$options->set('X-Joomla-Token', 'c2hhMjU2OjY0NDplZTBiZTBiOTgwNWU2OGU2YWY2OTZkM2JmOTVjYzVjMmQ2OTg4NzNjMjIwYWM0ZmMxNzQ4OThjM2E4OGMyYWU4');

		try
		{
			// Don't let the request take longer than 2 seconds to avoid page timeout issues
			$response = HttpFactory::getHttp($options, 'curl')->post($this->serverUrl, $content, null, 4);
		}
		catch (UnexpectedValueException $e)
		{
			// There was an error sending stats. Should we do anything?
			$msg = $e->getMessage();
		}
		catch (RuntimeException $e)
		{
			// There was an error connecting to the server or in the post request
			$msg = $e->getMessage();
		}
		catch (Exception $e)
		{
			// An unexpected error in processing; don't let this failure kill the site
			$msg = $e->getMessage();
		}
 
		return $msg;
	}

	private function sendData2($content)
	{
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->serverUrl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $content,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'X-Joomla-Token: c2hhMjU2OjY0NDplZTBiZTBiOTgwNWU2OGU2YWY2OTZkM2JmOTVjYzVjMmQ2OTg4NzNjMjIwYWM0ZmMxNzQ4OThjM2E4OGMyYWU4'
			),
		));

		$data = new stdClass;
		$data->body = curl_exec($curl);
		$data->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);
	//	print_r($response);
	//	exit();
		return $data;
	}

}
