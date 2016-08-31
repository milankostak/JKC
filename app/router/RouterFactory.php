<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter() {
		$router = new RouteList;

		// public part of the application
		$router[] = new Route("index.php", "Post:default", Route::ONE_WAY);
		$router[] = new Route("map", "Post:map");
		$router[] = new Route("1", "Post:default", Route::ONE_WAY);// number 1 redirect to default page
		$router[] = new Route("tag/<url>/1", "Post:tag", Route::ONE_WAY);// list of articles by tag - delete 1 for the first page
		$router[] = new Route("<page [0-9]+>", "Post:default");// pagination on default page
		$router[] = new Route("archive[/<year (19[789][0-9]{1}|20[0123][0-9]{1})>/<month (0[1-9]|1[012])>]", "Post:archive");
		$router[] = new Route("tag/<url>[/<page [0-9]+>]", "Post:tag");// pagination according to a tag, hide name of presenter
		$router[] = new Route("<action (post|comments)>/<url>", "Post:<action>");// hide name of presenter for comments, posts (and tags)
		$router[] = new Route("vote/<id>/<poll>/<url>", "Post:vote");// need to know this info when voting + hide name of presenter

		// admin part of the application
		$router[] = new Route("article/category/<year (19[789][0-9]{1}|20[0123][0-9]{1})>/<month (0[1-9]|1[012])>", "Article:category");
		$router[] = new Route("comment/<action (publish|unpublish|delete)>/<id>", "Comment:<action>");
		$router[] = new Route("poll/delete-poll/<article>/<poll>", "Poll:deletePoll");// deleting polls by link
		$router[] = new Route("tags/delete-tag/<article>/<tag>", "Tags:deleteTag");// deleting tags by link
		$router[] = new Route("article/<action (delete-tag|add-tag)>/<article>/<tag>", "Article:<action>");// deleting and adding tags for articles
	
		$router[] = new Route("<presenter>/<action>[/<id>]", "Post:default");
		return $router;
	}

}
