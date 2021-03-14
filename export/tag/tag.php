<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Export.tag
 *
 * @copyright   Copyright (C) 2021 Alikon. All rights reserved.
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
 * @since 3.9
 */
class PlgExportTag extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.9
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    ApplicationCms
	 * @since  3.9
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    DatabaseDriver
	 * @since  3.9
	 */
	protected $db;

	/**
	 * URL to send the data.
	 *
	 * @var    string
	 * @since  3.9
	 */
	protected $serverUrl = '';

	/**
	 * First step to enter the sampledata. Content.
	 *
	 * @return  array or void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.9
	 */
	public function onAjaxTag()
	{
		$id  = $this->app->input->get('id');
		$domain = $this->params->get('url', 'http://localhost');
		$endpoint = '/api/index.php/v1/tags';
		$this->serverUrl = $domain . $endpoint;

		// Get an instance of the generic tag model
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/models/', 'TagsModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables/');
		$model = JModelLegacy::getInstance('Tag', 'TagsModel', array('ignore_request' => true));

		$item = $model->getItem($id);

		unset($item->created_user_id);

		$content = json_encode($item);

		try
		{
			$response = $this->sendData($item);
		}
		catch (RuntimeException $e)
		{
			// There was an error sending data.
			$this->app->redirect(Route::_('index.php?option=com_tags&view=tag&layout=edit&id=' . $id, false), ' Connection ', 'error');
		}

		if ($response->code !== 200)
		{
			$data = json_decode($response->body);

			$this->app->redirect(Route::_('index.php?option=com_tags&view=tag&layout=edit&id=' . $id, false), $response->code . ' - ' . $data->errors[0]->title, 'error');
		}

		$this->app->redirect(Route::_('index.php?option=com_tags&view=tag&layout=edit&id=' . $id, false), 'Exported', 'success');

	}

	/**
	 * Send the data to the j4 server
	 *
	 * @return  boolean
	 *
	 * @since   3.9
	 *
	 * @throws  RuntimeException  If there is an error sending the data.
	 */
	private function sendData($item)
	{
		$content = json_encode($item);
		$options = new Registry;
		$options->set('Content-Type', 'application/json');
		$headers = array('Authorization' => 'Bearer ' . $this->params->get('key'));

		return  HttpFactory::getHttp($options)->post($this->serverUrl, $content, $headers, 2);
	}

}
