<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WhiteOctober\PagerfantaBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Pagerfanta\PagerfantaInterface;

/**
 * PagerfantaExtension.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
class PagerfantaExtension extends \Twig_Extension
{
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container A container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'pagerfanta'  => new \Twig_Function_Method($this, 'renderPagerfanta', array('is_safe' => array('html'))),
            'sortcol'  => new \Twig_Function_Method($this, 'setSortColumns', array('is_safe' => array('html'))),
        );
    }

    /**
     * Renders a pagerfanta.
     *
     * @param PagerfantaInterface $pagerfanta The pagerfanta.
     * @param string              $viewName   The view name.
     * @param array               $options    An array of options (optional).
     *
     * @return string The pagerfanta rendered.
     */
    public function renderPagerfanta(PagerfantaInterface $pagerfanta, $viewName, array $options = array())
    {
        $options = array_replace(array(
            'routeName'   => null,
            'routeParams' => array(),
        ), $options);

        $router = $this->container->get('router');
        $request = $this->container->get('request');

        if (null === $options['routeName']) {
            $options['routeName'] = $request->attributes->get('_route');
            $options['routeParams'] = $request->query->all();
            if ($options['routeName'] === '_internal') {
                throw new \Exception('PagerfantaBundle can not guess the route when used in a subrequest');
            }
            foreach ($router->getRouteCollection()->get($options['routeName'])->compile()->getVariables() as $variable) {
                $options['routeParams'][$variable] = $request->attributes->get($variable);
            }
        }

        $routeName = $options['routeName'];
        $routeParams = $options['routeParams'];
        $routeGenerator = function($page) use($router, $routeName, $routeParams) {
            return $router->generate($routeName, array_merge($routeParams, array('page' => $page)));
        };

        return $this->container->get('white_october_pagerfanta.view_factory')->get($viewName)->render($pagerfanta, $routeGenerator, $options);
    }
    
    /**
     * Renders a pagerfanta.
     *
     * @param string              $sortCol    The column order name
     * @param array               $options    An array of options (optional).
     *
     * @return string The pagerfanta rendered.
     */
    public function setSortColumns($sortCol, array $options = array())
    {
        $options = array_replace(array(
            'routeName'   => null,
            'routeParams' => array('order' => 'id', 'dir' => 'asc'),
            'target' => null,
            'name' => ucwords(str_replace('_',' ', $sortCol)),
        ), $options);

        $router = $this->container->get('router');
        $request = $this->container->get('request');

        if (null === $options['routeName']) {
            $options['routeName'] = $request->attributes->get('_route');
            $options['routeParams'] = array_replace($options['routeParams'], $request->query->all());
            if ($options['routeName'] === '_internal') {
                throw new \Exception('PagerfantaBundle can not guess the route when used in a subrequest');
            }
            foreach ($router->getRouteCollection()->get($options['routeName'])->compile()->getVariables() as $variable) {
                $options['routeParams'][$variable] = $request->attributes->get($variable);
            }
        }

        
        $routeName = $options['routeName'];
        $routeParams = $options['routeParams'];
        $is_active = ($routeParams['order'] === $sortCol);
        $routeParams['dir'] = ($is_active && $routeParams['dir'] === 'asc') ? 'desc' : 'asc';
        $route = $router->generate($routeName, array_merge($routeParams, array('order' => $sortCol)));

        $triangle = ($is_active) ? ' <span class="triangle">'. (($routeParams['dir'] === 'asc') ? '&#9660;' : '&#9650;') .'</span>' : '';
        $target = (!empty($options['target'])) ? ' target="'. $options['target'] .'"' : '';
        $class = ($is_active) ? ' class="active"' : '';
        return '<a href="'. $route .'"'. $target . $class .'>'. $options['name'] . $triangle .'</a>';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pagerfanta';
    }
}
