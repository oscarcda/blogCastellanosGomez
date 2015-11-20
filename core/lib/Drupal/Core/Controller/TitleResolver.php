<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\TitleResolver.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides the default implementation of the title resolver interface.
 */
class TitleResolver implements TitleResolverInterface {
  use StringTranslationTrait;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a TitleResolver instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $string_translation) {
    $this->controllerResolver = $controller_resolver;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) {
    $route_title = NULL;
    // A dynamic title takes priority. Route::getDefault() returns NULL if the
    // named default is not set.  By testing the value directly, we also avoid
    // trying to use empty values.
    if ($callback = $route->getDefault('_title_callback')) {
      $callable = $this->controllerResolver->getControllerFromDefinition($callback);
      $arguments = $this->controllerResolver->getArguments($request, $callable);
      $route_title = call_user_func_array($callable, $arguments);
    }
    elseif ($title = $route->getDefault('_title')) {
      $options = array();
      if ($context = $route->getDefault('_title_context')) {
        $options['context'] = $context;
      }
      $args = array();
      if (($raw_parameters = $request->attributes->get('_raw_variables'))) {
        foreach ($raw_parameters->all() as $key => $value) {
          $args['@' . $key] = $value;
          $args['%' . $key] = $value;
        }
      }
      if ($title_arguments = $route->getDefault('_title_arguments')) {
        $args = array_merge($args, (array) $title_arguments);
      }

      // Fall back to a static string from the route.
      $route_title = $this->t($title, $args, $options);
    }
    return $route_title;
  }

}
