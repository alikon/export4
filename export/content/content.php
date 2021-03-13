<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Job.jobone
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * Joomla! Job One plugin
 *
 * An example for a job plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgExportContent extends CMSPlugin
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
	 * URL to get the data.
	 *
	 * @var    string
	 * @since  3.5
	 */
	protected $getUrl = '';

	/**
	 * URL to send the data.
	 *
	 * @var    string
	 * @since  3.5
	 */
	protected $postUrl = '';

	/**
	 * First step to enter the sampledata. Content.
	 *
	 * @return  array or void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxContent()
	{
		$id  = $this->app->input->get('id');
		$domain = $this->params->get('url', 'http://localhost');
		$this->postUrl = $domain . '/api/index.php/v1/content/article';
		$this->getUrl = $domain . '/api/index.php/v1/content';


		// Get an instance of the generic articles model
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models', 'ArticleModel');
		$model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));

		$item = $model->getItem($id);
		$item->catid = $this->params->get('catid');
		unset($item->created_by);

		//var_dump($item);exit();
		try
		{
			$response = $this->sendData($item);
		}
		catch (RuntimeException $e)
		{
			// There was an error sending data.
			$this->app->redirect(Route::_('index.php?option=com_content&view=article&layout=edit&id=' . $id, false), ' Connection ', 'error');
		}

		if ($response->code !== 200)
		{
			$data = json_decode($response->body);

			$this->app->redirect(Route::_('index.php?option=com_content&view=article&layout=edit&id=' . $id, false), $response->code . ' - ' . $data->errors[0]->title, 'error');
		}

		$this->app->redirect(Route::_('index.php?option=com_content&view=article&layout=edit&id=' . $id, false), 'Exported', 'success');

	}

	/**
	 * Send the data to the j4 server
	 *
	 * @return  boolean
	 *
	 * @since   3.5
	 *
	 * @throws  RuntimeException  If there is an error sending the data.
	 */
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

		return  HttpFactory::getHttp($options)->post($this->postUrl, $content, $headers, 2);
	}

	/**
	 * Check category existence
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
		return HttpFactory::getHttp($options)->get($this->getUrl . '/categories/'. $catid, $headers, 2);
	}
}
