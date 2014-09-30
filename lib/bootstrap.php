<?php

/**
 * Bootstrapper
 *
 * Initializes the application and framework with environment information
 * necessary for functionality.
 */
 
namespace Base;

// Turn on error reporting
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

// Initialize app's web path
Path::initDir(getcwd());

// Initialize app's base URI
Path::initURI();

// Init the App to read configuration
App::init();