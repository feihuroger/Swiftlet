<?php
/**
 * @package Swiftlet
 * @copyright 2009 ElbertF http://elbertf.com
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 */

/**
 * Page
 * @abstract
 */
class Page_Controller extends Controller
{
	public
		$pageTitle    = 'Page',
		$dependencies = array('db', 'node', 'page')
		;

	function init()
	{
		die('x');
		$language = !empty($this->app->lang->ready) ? $this->app->lang->language : 'English US';

		$this->app->db->sql('
			SELECT
				p.`id`,
				p.`node_id`,
				p.`title`,
				p.`body`,
				p.`published`,
				n.`home`
			FROM      `' . $this->app->db->prefix . 'pages` AS p
			LEFT JOIN `' . $this->app->db->prefix . 'nodes` AS n ON p.`node_id` = n.`id`
			WHERE
				' . ( $this->app->input->args[0] ? '
				(
					n.`id`   =  ' . ( int ) $this->app->input->args[0] . ' OR' : '' ) . '
					n.`path` = "' . $this->app->db->escape($this->request)            . '"
				) AND
				n.`type`      = "page"                                      AND
				' . ( !$this->app->permission->check('admin page edit') ? '
				p.`published` = 1                                           AND
				' : '' ) . '
				p.`lang`      = "' . $this->app->db->escape($language) . '"
			LIMIT 1
			;');

		if ( $r = $this->app->db->result )
		{
			$this->pageTitle = $r[0]['title'];

			$this->app->view->nodeId    = $r[0]['node_id'];
			$this->app->view->body      = $this->app->view->allow_html($r[0]['body']);
			$this->app->view->home      = $r[0]['home'];

			/*
			 * Prefix relative links with the path to the root
			 * This way internal links won't break when the site
			 * is moved to another directory
			 */
			$this->app->page->parse_urls($this->app->view->body);

			/*
			 * Create a breadcrumb trail
			 */
			$this->app->view->parents = array();

			if ( !$r[0]['home'] )
			{
				$nodes = $this->app->node->get_parents($r[0]['node_id']);

				foreach ( $nodes['parents'] as $d )
				{
					if ( $d['id'] != Node_Plugin::ROOT_ID )
					{
						$this->app->view->parents[$d['path'] ? $d['path'] : 'node/' . $d['id']] = $d['title'];
					}
				}
			}

			if ( !$r[0]['published'] )
			{
				$this->app->view->notice = $this->app->view->t('This page has not been published.');
			}
		}

		if ( !isset($this->app->view->nodeId) )
		{
			header('HTTP/1.0 404 Not Found');

			$this->pageTitle = $this->app->view->t('Page not found');

			$this->app->view->load('404.html.php');
		}
		else
		{
			$this->app->view->load('page.html.php');
		}
	}
}
