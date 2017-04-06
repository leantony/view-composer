<?php

namespace Leantony\ViewComposer;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\View\View;
use InvalidArgumentException;

abstract class ViewComposer
{
    /**
     * App instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Cache instance
     *
     * @var Repository
     */
    protected $cache;

    /**
     * Overrides config value
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * How long the items should be cached, in minutes
     * Overrides config value, if config value isn't provided
     *
     * @var int
     */
    protected $cacheFor = 0;

    /**
     * Specify if cache tags should be used
     *
     * @var bool
     */
    protected $tags = false;

    /**
     * Compose the View
     *
     * @param View $view
     *
     * @return void
     */
    public function compose(View $view)
    {
        $this->app = app();

        $var = $this->checkVariableName($this->specifyOutputVariable());

        // no caching enabled
        $caching = $this->app['config']->get('view_composers.cache', false);
        // check the config, then var
        if (!$caching || !$this->useCache) {
            $data = $this->getData();
            $view->with($var, $data);

        } else {

            $this->cache = $this->app['cache'];

            if (!$this->tags) {
                // caching enabled
                $key = $this->getKey($var);
                if ($this->cache->has($key)) {
                    $view->with($var, $this->cache->get($key));

                } else {

                    $this->checkCache($view, $key, $var);
                }
            } else {
                $key = $this->getKey($var);
                $this->checkTaggedCache($view, $key, $var);
            }

        }
    }

    /**
     * Checks if the provided variable name is okay
     *
     * @return string
     */
    protected function checkVariableName()
    {
        $arg = func_get_args()[0];
        if (preg_match('/^[a-zA-Z0-9_]*+/', $arg)) {
            return $arg;
        }
        throw new InvalidArgumentException("The argument ${arg} has to be a valid variable name.");
    }

    /**
     * Specify the variable name that will be sent to the view representing the data
     *
     * @return string
     */
    public abstract function specifyOutputVariable();

    /**
     * Gets the data from the data source provided, which will be displayed in the view
     *
     * @return mixed
     */
    abstract public function getData();

    /**
     * Check for items in the cache
     *
     * @param View $view
     * @param $key
     * @param $var
     * @return void
     */
    protected function checkCache(View $view, $key, $var)
    {
        if ($this->cache->has($key)) {
            $view->with($var, $this->cache->get($key));

        } else {

            $data = $this->getData();
            $this->cache->put($key, $data,
                $this->app['config']->get('view_composers.caching_for', $this->cacheFor));
            $view->with($var, $data);
        }
    }

    /**
     * Check for items in the cache
     *
     * @param View $view
     * @param $key
     * @param $var
     * @return void
     */
    protected function checkTaggedCache(View $view, $key, $var)
    {
        $tags = $this->getTags();
        if (empty($tags)) {
            throw new \LogicException("Cannot do tags on an empty tags list. Override the getTags() method to add tags for this view composer");
        }
        $cacheData = $this->cache->tags($tags)->get($key);
        if ($cacheData) {
            $view->with($var, $cacheData);
        } else {
            $data = $this->getData();
            $this->cache->tags($this->getTags())
                ->put($key, $data, $this->app['config']->get('view_composers.caching_for', $this->cacheFor));
            $view->with($var, $data);
        }
    }

    /**
     * Tags to be used, if required
     *
     * @return array
     */
    protected function getTags()
    {
        return [];
    }

    /**
     * Get the key of the cached view data
     *
     * @param $var
     * @return string
     */
    protected function getKey($var)
    {
        return hash('sha1', $var);
    }
}