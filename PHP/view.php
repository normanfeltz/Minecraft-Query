<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

require_once('MinecraftQuery.php');
require_once('MinecraftQueryException.php');

use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

$timer = microtime(true);
$query = new MinecraftQuery();

try
{
	$query->connect('localhost', 25580, 1);
}
catch(MinecraftQueryException $e)
{
	$exception = $e;
}

$timer = number_format(microtime(true) - $timer, 4, '.', '');
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    	<meta charset="utf-8">
    	<title>Minecraft Query PHP Class</title>

    	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    	<style type="text/css">
    		.jumbotron
            {
    			margin-top: 30px;
    			border-radius: 0;
    		}

    		.table thead th
            {
    			background-color: #428BCA;
    			border-color: #428BCA !important;
    			color: #FFF;
    		}
    	</style>
    </head>
    <body>
        <div class="container">
            <div class="jumbotron">
                <h1>Minecraft Query PHP Class</h1>
                <p>This class was created to query Minecraft servers. It works starting from Minecraft 1.0.</p>
            </div>
            <?php 
            if (isset($exception))
            {
            ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <?php echo htmlspecialchars($exception->getMessage()); ?>
                    </div>
                    <p>
                        <?php echo nl2br($exception->getTraceAsString(), false); ?>
                    </p>
                </div>
            <?php   
            }
            else
            {
                ?>
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th colspan="2">
                                        Server Info <em>(queried in <?php echo $timer; ?>s)</em>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (($info = $query->getInfo()) !== false)
                                {
                                    foreach($info as $infoKey => $infoValue)
                                    {
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($infoKey); ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (is_array($infoValue))
                                                {
                                                    echo "<pre>";
                                                    print_r($infoValue);
                                                    echo "</pre>";
                                                }
                                                else
                                                {
                                                    echo htmlspecialchars($infoValue);
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tr>
                                        <td colspan="2">
                                            No information received
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        Players
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (($players = $query->getPlayers()) !== false)
                                {
                                    foreach($players as $player)
                                    {
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($player); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tr>
                                        <td>
                                            No players in da house
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </body>
</html>
