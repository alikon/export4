<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Export.contact
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
class PlgExportContact extends CMSPlugin
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
	public function onAjaxContact()
	{
		$id  = $this->app->input->get('id');
		$ext  = $this->app->input->get('ext');
		$domain = $this->params->get('url', 'http://localhost');
		$key = $this->params->get('key');
		$endpoint = '/api/index.php/v1/contact';
		$this->serverUrl = $domain . $endpoint;

		// Get an instance of the generic contact model
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_contact/models/', 'ContactModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_contact/tables/');
		$model = JModelLegacy::getInstance('Contact', 'ContactModel', array('ignore_request' => true));

		$item = $model->getItem($id);

		unset($item->created_by);
		$item->catid =  $this->params->get('catid');

		$response = $this->sendData($item);

		if ($response->code !== 200)
		{
			$data = json_decode($response->body);

			$this->app->redirect(JRoute::_('index.php?option=com_contact&view=contact&layout=edit&id=' . $id, false), $response->code . ' - ' . $data->errors[0]->title, 'error');
		}

		$this->app->redirect(JRoute::_('index.php?option=com_contact&view=contact&layout=edit&id=' . $id, false), 'Exported', 'success');

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
	private function checkCategory($catid)
	{
		$options = new Registry;
		$options->set('Content-Type', 'application/json');
		$headers = array('Authorization' => 'Bearer ' . $this->params->get('key'));

		// Don't let the request take longer than 2 seconds to avoid page timeout issues
		return HttpFactory::getHttp($options)->get($this->serverUrl . '/categories/'. $catid, $headers, 2);
	}

	private function sendData($item)
	{
		$response = $this->checkCategory($item->catid);
		
		if ($response->code !== 200)
		{
			return $response;
		}

		$content = json_encode($item);
		$options = new Registry;
		$options->set('Content-Type', 'application/json');
		$headers = array('Authorization' => 'Bearer ' . $this->params->get('key'));

		$response = HttpFactory::getHttp($options)->post($this->serverUrl, $content, $headers, 2);

		return $response;
	}
}
