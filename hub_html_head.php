<?php

use function Vanderbilt\HarmonistHubExternalModule\getFile;

?>
<head>
    <title><?=$settings['hub_name_title']?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="Cache-control" content="public">
    <meta name="theme-color" content="#fff">
    <link rel="icon" type="image/x-icon" href="<?=getFile($module, $pidsArray['PROJECTS'], $settings['hub_logo_favicon'],'favicon')?>">

    <?php include_once("head_scripts.php");?>

    <script type='text/javascript'>
        $(document).ready(function() {
            Sortable.init();
            $('[data-toggle="tooltip"]').tooltip();

            var CACHE_NAME = 'iedea-site-cache';
            var urlsToCache = [
                '/',
                '/css/style.css',
                '/js/base.js',
                '/js/functions.js'
            ];

            self.addEventListener('install', function(event) {
                // Perform install steps
                event.waitUntil(
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            return cache.addAll(urlsToCache);
                        })
                );
            });

            var pageurloption = <?=json_encode($option)?>;
            if(pageurloption != '') {
                $('[option=' + pageurloption + ']').addClass('navbar-active');
            }

        } );
    </script>

    <style>
        table thead .glyphicon {
            color: blue;
        }
    </style>
</head>
