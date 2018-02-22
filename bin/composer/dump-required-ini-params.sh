#!/usr/bin/env php
<?php

/*
 * This should be run in the VM in order to dump required INI configuration values to a YAML parameter file that
 * can be used by Symfony
 */

$currentPath  = realpath(__DIR__);
$projectRoot  = $currentPath . '/../../';
$autoloadPath = $projectRoot . 'vendor/autoload.php';
$ymlDumpPath  = $projectRoot . 'app/config/ini_parameters.yml';

require_once($autoloadPath);

$loader     = new OpenConext\EngineBlockBridge\Configuration\EngineBlockIniFileLoader();
$iniContent = $loader->load(
    array(
        $projectRoot . 'application/' . EngineBlock_Application_Bootstrapper::CONFIG_FILE_DEFAULT,
        EngineBlock_Application_Bootstrapper::CONFIG_FILE_ENVIRONMENT,
    )
);

$config = new \OpenConext\EngineBlockBridge\Configuration\EngineBlockConfiguration($iniContent);

$trustedProxies = $config->get('trustedProxyIps', array());
if (!is_array($trustedProxies)) {
    $trustedProxies = $trustedProxies->toArray();
}

$encryptionKeys = $config->get('encryption.keys', array());
if (!is_array($encryptionKeys)) {
    $encryptionKeys = $encryptionKeys->toArray();
}

$ymlContent = array(
    'parameters' => array(
        // Setting the debug mode to true will cause EngineBlock to display
        // more information about errors that have occurred and it will show
        // the messages it sends and receives for the authentication.
        //
        // NEVER TURN THIS ON FOR PRODUCTION!
        //
        // Note: this setting is independent from Symfony debug mode.
        'debug'                                                   => $config->get('debug', false),

        'domain'                                                  => $config->get('base_domain'),
        'trusted_proxies'                                         => $trustedProxies,
        'encryption_keys'                                         => $encryptionKeys,
        'api.users.janus.username'                                => $config->get('engineApi.users.janus.username'),
        'api.users.janus.password'                                => $config->get('engineApi.users.janus.password'),
        'api.users.profile.username'                              => $config->get('engineApi.users.profile.username'),
        'api.users.profile.password'                              => $config->get('engineApi.users.profile.password'),
        'pdp.host'                                                => $config->get('pdp.host'),
        'pdp.policy_decision_point_path'                          => $config->get('pdp.policy_decision_point_path'),
        'pdp.username'                                            => $config->get('pdp.username'),
        'pdp.password'                                            => $config->get('pdp.password'),
        'pdp.client_id'                                           => $config->get('pdp.client_id'),
        'attribute_aggregation.base_url'                          => $config->get('attributeAggregation.baseUrl'),
        'attribute_aggregation.username'                          => $config->get('attributeAggregation.username'),
        'attribute_aggregation.password'                          => $config->get('attributeAggregation.password'),
        'logger.channel'                                          => $config->get('logger.conf.name'),
        'logger.fingers_crossed.passthru_level'                   => $config->get(
            'logger.conf.handler.fingers_crossed.conf.passthru_level'
        ),
        'logger.syslog.ident'                                     => $config->get('logger.conf.handler.syslog.conf.ident'),
        'database.host'                                           => $config->get('database.host'),
        'database.port'                                           => $config->get('database.port'),
        'database.user'                                           => $config->get('database.user'),
        'database.password'                                       => $config->get('database.password'),
        'database.dbname'                                         => $config->get('database.dbname'),
        'database.test.host'                                      => $config->get('database.test.host'),
        'database.test.port'                                      => $config->get('database.test.port'),
        'database.test.user'                                      => $config->get('database.test.user'),
        'database.test.password'                                  => $config->get('database.test.password'),
        'database.test.dbname'                                    => $config->get('database.test.dbname'),
        'cookie.locale.domain'                                    => $config->get('cookie.lang.domain'),
        'cookie.locale.expiry'                                    => (int) $config->get('cookie.lang.expiry'),
        'cookie.locale.http_only'                                 => (bool) $config->get('cookie.lang.http_only', false),
        'cookie.locale.secure'                                    => (bool) $config->get('cookie.lang.secure', false),
        'feature_eb_encrypted_assertions'                         => (bool) $config->get('engineblock.feature.encrypted_assertions'),
        'feature_eb_encrypted_assertions_require_outer_signature' => (bool) $config->get('engineblock.feature.encrypted_assertions_require_outer_signature'),
        'feature_api_metadata_push'                               => (bool) $config->get('engineApi.features.metadataPush'),
        'feature_api_consent_listing'                             => (bool) $config->get('engineApi.features.consentListing'),
        'feature_api_metadata_api'                                => (bool) $config->get('engineApi.features.metadataApi'),
        'feature_run_all_manipulations_prior_to_consent'          => (bool) $config->get('engineblock.feature.run_all_manipulations_prior_to_consent'),
        'minimum_execution_time_on_invalid_received_response'     => (int) $config->get('minimumExecutionTimeOnInvalidReceivedResponse'),
        'wayf.cutoff_point_for_showing_unfiltered_idps'           => (int) $config->get('wayf.cutoffPointForShowingUnfilteredIdps'),
        'wayf.remember_choice'                                    => (bool) $config->get('wayf.rememberChoice', false),
        'time_frame_for_authentication_loop_in_seconds'           => (int) $config->get('engineblock.timeFrameForAuthenticationLoopInSeconds'),
        'maximum_authentication_procedures_allowed'               => (int) $config->get('engineblock.maximumAuthenticationProceduresAllowed'),
        'view_default_layout'                                     => $config->get('defaults.layout'),
        'view_default_title'                                      => $config->get('defaults.title'),
        'view_default_header'                                     => $config->get('defaults.header'),
        'ui_return_to_sp_link'                                    => (bool) $config->get('ui.return_to_sp_link.active'),

        // Store attributes with their values, meaning that if an Idp suddenly
        // sends a new value (like a new e-mail address) consent has to be
        // given again.
        'consent_store_values'                                    => (bool) $config->get('authentication.storeValues', true),
    )
);

$ymlToDump = \Symfony\Component\Yaml\Yaml::dump($ymlContent, 4, 4);

$comment = "# This file is auto-generated" . PHP_EOL;

$writer = new \Symfony\Component\Filesystem\Filesystem();
$writer->dumpFile($ymlDumpPath, $comment . $ymlToDump, 0664);
