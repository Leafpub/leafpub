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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     *
     *
     **/
    public function author(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = User::render($args['author'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the custom homepage
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function customHomepage(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Post::render(Setting::getOne('homepage'));

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the blog view
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function blog(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
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
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     */
    public function error(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Error::render();

        return $response->write($html);
    }

    /**
     * Generates the RSS Feed
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     */
    public function feed(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Feed::render([
            'author' => $request->getParams()['author'],
            'tag' => $request->getParams()['tag'],
        ]);

        return $html !== '' ?
            $response
                ->withHeader('Content-type', 'application/xml')
                ->write($html) :
            $this->notFound($request, $response);
    }

    /**
     * Renders a specific post
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function post(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
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

    public function ampify(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
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
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     */
    public function search(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Search::render($args['query'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    /**
     * Renders the tag view
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     */
    public function tag(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Tag::render($args['tag'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function sitemap(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $xml = \Leafpub\Leafpub::generateSitemap();

        return $xml === false ?
            $this->notFound($request, $response) :
            $response->withHeader('Content-type', 'application/xml')->write($xml);
    }
}
