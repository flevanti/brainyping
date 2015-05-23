<?php

class page_renderer {
    public $pageTitle = "";
    public $scriptsFromLocal = true;
    public $metaDescription = "Server Status Monitor - Be the first to know when a server goes down";

    function sendPageHeader() {
        header('Content-Type: text/html; charset=utf-8');
    }

    function htmlHtmlInit() {
        return "<!DOCTYPE html>
                <html lang='en'>";
    }

    function htmlHtmlEnd() {
        return "</html>";
    }

    function htmlHead() {
        $content = "
                <head>
                    " . $this->favicon() . "
                    " . $this->htmlMeta() . "
                    " . $this->htmlPageTitle() . "
                    " . $this->includeScripts() . "
                </head>
                ";

        return $content;
    }

    private function includeScripts() {
        if ($this->scriptsFromLocal == true) {
            $content = $this->includeScriptFromLocal();
        } else {
            $content = $this->includeScriptFromRemote();
        }
        $content .= "
                        <script src='/js/jquery-ui.min.js'></script>
                <link href='/css/jquery-ui.min.css' rel='stylesheet'>
                <link href='/css/jquery-ui.structure.min.css' rel='stylesheet'>
                <link href='/css/jquery-ui.theme.min.css' rel='stylesheet'>
                <!--PEITY CHARTS-->
                <script src='/js/jquery.peity.js'></script>
                <!--tablesorter JQUERY plugin-->
                <script src='/js/jquery.tablesorter.min.js'></script>
                <!--sticky footer and navbar-->
                <link href='/css/sticky-footer-navbar.css' rel='stylesheet' >
                <!-- Custom styles for this template -->
                <link href='/css/general.css' rel='stylesheet'>
                <!--JSCHART.ORG -->
                <script src='/js/Chart.min.js'></script>
                <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
                <!--[if lt IE 9]>
                <script src='/js/html5shiv.min.js'></script>
                <script src='/js/respond.min.js'></script>
                <![endif]-->
                <!--toastr CSS alert box-->
                <link href='/css/toastr.min.css' rel='stylesheet'>
                <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
                <script src='/js/ie10-viewport-bug-workaround.js'></script>
                <!-- toastr alertbox-->
                <script src='/js/toastr.min.js'></script>
        "; //End of content variable
        return $content;
    }

    private function includeScriptFromLocal() {
        return "
                <script src='/js/jquery-1.11.1.min.js'></script> <!--JQUERY 1.11.1-->
                <link href='/css/normalize.css' rel='stylesheet'> <!--NORMALIZE CSS-->
                <link href='/css/font-awesome.min.css' rel='stylesheet'> <!--FONT AWESOME-->
                <link href='/css/bootstrap.min.css' rel='stylesheet'> <!--BOOTSTRAP 3.2.0-->
                <script src='/js/bootstrap.min.js'></script>
         ";
    }

    private function includeScriptFromRemote() {
        return "
                <script src='https://code.jquery.com/jquery-1.11.1.min.js'></script><!--JQUERY 1.11.1-->
                <link  href='https://normalize-css.googlecode.com/svn/trunk/normalize.css' rel='stylesheet'/> <!--NORMALIZE CSS-->
                <link href='https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' rel='stylesheet'> <!--FONT AWESOME-->
                <link href='https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css' rel='stylesheet'> <!--BOOTSTRAP 3.2.0-->
                <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js'></script>

        ";
    }

    private function htmlMeta() {
        return "
                <meta charset='utf-8'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <meta name='description' content='" . $this->metaDescription . "'>
                <meta name='author' content='Francesco Levanti'>
                ";
    }

    private function htmlPageTitle() {
        return "<title>" . $this->pageTitle . "</title>";
    }

    private function favicon() {
        return "
                <!--FAVICON-->
                <link rel='shortcut icon' href='/favicon.ico' />
                <link rel='icon' type='image/x-icon' href='/favicon.ico' />
        ";
    }

    function htmlBodyInit() {
        return "
                <body>
        ";
    }

    function bodyContainer() {
        return "
                    <!-- Begin page content -->
                    <div class='container'>
                ";
    }

    function bodyContainerEnd() {
        return "</div>";
    }

    function htmlBodyEnd() {
        return "</body>";
    }
} //End of Class