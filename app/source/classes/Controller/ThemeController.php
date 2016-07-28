<?php
//
// Controller for theme views
//
namespace Postleaf\Controller;

use Postleaf\Blog,
    Postleaf\Error,
    Postleaf\Feed,
    Postleaf\Post,
    Postleaf\Search,
    Postleaf\Session,
    Postleaf\Setting,
    Postleaf\Tag,
    Postleaf\User;

class ThemeController extends Controller {

    public function author($request, $response, $args) {
        $html = User::render($args['author'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function customHomepage($request, $response, $args) {
        $html = Post::render(Setting::get('homepage'));

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function blog($request, $response, $args) {
        $html = Blog::render($args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function error($request, $response, $args) {
        $html = Error::render();
        return $response->write($html);
    }

    public function feed($request, $response, $args) {
        $html = Feed::render([
            'author' => $request->getParams()['author'],
            'tag' => $request->getParams()['tag']
        ]);

        return $html ?
            $response
                ->withHeader('Content-type', 'application/xml')
                ->write($html) :
            $this->notFound($request, $response);
    }

    public function post($request, $response, $args) {
        $html = Post::render($args['post'], [
            // Render this post as a preview if the user is logged in and ?preview is in the URL
            'preview' => Session::isAuthenticated() && isset($request->getParams()['preview'])
        ]);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function search($request, $response, $args) {
        $html = Search::render($args['query'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

    public function tag($request, $response, $args) {
        $html = Tag::render($args['tag'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

}