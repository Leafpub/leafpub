<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Controller;

use Leafpub\Blog;
use Leafpub\Error;
use Leafpub\Events\Post\PostViewed;
use Leafpub\Feed;
use Leafpub\Models\Post;
use Leafpub\Models\Setting;
use Leafpub\Models\Tag;
use Leafpub\Models\User;
use Leafpub\Search;
use Leafpub\Session;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * ThemeController
 *
 * Controller for frontend views
 *
 **/
class ThemeController extends Controller
{
    /**
     * Renders the author page
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     *
     **/
    public function author(
        Request $request,
        Response $response,
        array $args
    ): Response
    {
        $html = User::render($args['author'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the custom homepage
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function customHomepage($request, $response, $args)
    {
        $html = Post::render(Setting::getOne('homepage'));

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the blog view
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function blog($request, $response, $args)
    {
        $page = isset($args['page']) ? $args['page'] : false;
        $html = Blog::render($page);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the error view
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function error($request, $response, $args)
    {
        $html = Error::render();

        return $response->write($html);
    }

    /**
     * Generates the RSS Feed
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function feed($request, $response, $args)
    {
        $html = Feed::render([
            'author' => $request->getParams()['author'],
            'tag' => $request->getParams()['tag'],
        ]);

        return $html ?
            $response
                ->withHeader('Content-type', 'application/xml')
                ->write($html) :
            $this->notFound($request, $response);
    }

    /**
     * Renders a specific post
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function post($request, $response, $args)
    {
        $preview = Session::isAuthenticated() && isset($request->getParams()['preview']);
        $html = Post::render($args['post'], [
            // Render this post as a preview if the user is logged in and ?preview is in the URL
            'preview' => $preview,
        ]);

        if ($html === false) {
            return $this->notFound($request, $response);
        }
        if (!$preview) {
            $ev = new PostViewed(['post' => $args['post'], 'request' => $request]);
            \Leafpub\Leafpub::dispatchEvent(PostViewed::NAME, $ev);
        }

        return $response->write($html);
    }

    public function ampify($request, $response, $args)
    {
        $preview = Session::isAuthenticated() && isset($request->getParams()['preview']);
        $html = Post::render($args['post'], [
            // Render this post as a preview if the user is logged in and ?preview is in the URL
            'preview' => $preview,
            'amp' => true,
        ]);

        if ($html === false) {
            return $this->notFound($request, $response);
        }
        if (!$preview) {
            $ev = new PostViewed(['post' => $args['post'], 'request' => $request]);
            \Leafpub\Leafpub::dispatchEvent(PostViewed::NAME, $ev);
        }

        return $response->write($html);
    }

    /**
     * Renders the search results
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function search($request, $response, $args)
    {
        $html = Search::render($args['query'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the tag view
     *
     * @param Request  $request
     * @param Response $response
     * @param array               $args
     *
     * @return Response
     *
     **/
    public function tag($request, $response, $args)
    {
        $html = Tag::render($args['tag'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function sitemap($request, $response, $args)
    {
        $xml = \Leafpub\Leafpub::generateSitemap();

        return $html === false ?
            $this->notFound($request, $response) :
            $response->withHeader('Content-type', 'application/xml')->write($xml);
    }
}
