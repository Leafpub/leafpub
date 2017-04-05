<?php
namespace Leafpub;
require_once(dirname(dirname(__DIR__)) . '/source/runtime.php');

// Deny if already installed
if(Leafpub::isInstalled()) {
    header('HTTP/1.0 403 Forbidden');
    exit();
}
?>
<!DOCTYPE html>
<html data-leafpub-error="true">
<head>
    <title>Install Leafpub</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, maximum-scale=1, minimal-ui">
    <link rel="shortcut icon" href="../assets/img/logo-color.png">
    <link rel="stylesheet" href="../assets/css/lib.css">
    <script src="../assets/js/jquery.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,300italic,600,600italic">
</head>
<body class="no-menu">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 push-lg-4 col-md-6 push-md-3 col-sm-8 push-sm-1">
                <div class="logo text-center">
                    <a href="https://www.leafpub.org/" target="_blank">
                        <img class="logo mt-3 mb-2" src="../assets/img/logo-color.png" alt="Logo" width="100" height="100">
                    </a>
                </div>
                <h2 class="text-center">Install Leafpub</h2>
                <p class="text-center text-muted">
                    Simple, beautiful publishing
                </p>

                <form id="installer-form" class="mt-3" action="<?=htmlspecialchars(Leafpub::url('source/installer/install.php'))?>" autocomplete="off">
                    <!-- Owner -->
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" class="form-control" name="name" id="name" autofocus>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="inner-addon-group">
                            <span class="inner-addon"><i class="fa fa-envelope-o"></i></span>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="username">Pick a Username</label>
                                <div class="inner-addon-group">
                                    <span class="inner-addon"><i class="fa fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" id="username" autofocus>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="password">Create a Password</label>
                                <div class="inner-addon-group">
                                    <span class="inner-addon"><i class="fa fa-key"></i></span>
                                    <input type="password" class="form-control" name="password" id="password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database -->
                    <h4 class="mt-3">
                        Database
                    </h4>
                    <div class="card card-outline-danger mb-3 text-center">
                        <small><strong>Warning!</strong> All existing data will be deleted!</small>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="driver">Database System</label>
                                <select class="form-control" name="driver" id="driver">
                                    <option value="Mysqli">Mysqli</option>
                                    <option value="Sqlsrv">Sqlsrv</optionn> 
                                    <option value="Pdo_Sqlite">SQLite</option> 
                                    <option value="Pdo_Mysql" selected>MySQL</option>
                                    <option value="Pdo">PostgreSQL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label for="db-host">Host</label>
                                <input type="text" class="form-control" name="db-host" id="db-host" placeholder="localhost">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="db-post">Port</label>
                                <input type="number" class="form-control" name="db-port" id="db-port" min="0" placeholder="3306">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="db-user">User</label>
                        <input type="text" class="form-control" name="db-user" id="db-user">
                    </div>
                    <div class="form-group">
                        <label for="db-password">Password</label>
                        <input type="password" class="form-control" name="db-password" id="db-password">
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="db-database">Database Name</label>
                                <input type="text" class="form-control" name="db-database" id="db-database">
                                 <small class="text-muted">
                                    Database name or path to SQLite file
                                </small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="db-prefix">Table Prefix</label>
                                <input type="text" class="form-control" name="db-prefix" id="db-prefix" placeholder="leafpub_">
                            </div>
                        </div>
                    </div>

                    <!-- Install -->
                    <div class="form-group m-y-2">
                        <input type="hidden" name="cmd" value="install">
                        <button type="submit" class="btn btn-lg btn-block btn-primary">Install</button>
                    </div>

                    <div class="text-center text-muted m-y-2">
                        Need help?
                        <a href="https://community.leafpub.org/" target="_blank">community.leafpub.org</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/lib.min.js"></script>
    <script src="../assets/js/installer.min.js"></script>
</body>
</html>