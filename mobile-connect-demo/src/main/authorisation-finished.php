<!--
/**
 *                          SOFTWARE USE PERMISSION
 *
 *  By downloading and accessing this software and associated documentation 
 *  files ("Software") you are granted the unrestricted right to deal in the 
 *  Software, including, without limitation the right to use, copy, modify, 
 *  publish, sublicense and grant such rights to third parties, subject to the
 *  following conditions:
 *
 *  The following copyright notice and this permission notice shall be included
 *  in all copies, modifications or substantial portions of this Software:
 *  Copyright © 2016 GSM Association.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS," WITHOUT WARRANTY OF ANY KIND, INCLUDING
 *  BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
 *  IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE. YOU AGREE TO INDEMNIFY AND HOLD HARMLESS THE AUTHORS AND COPYRIGHT
 *  HOLDERS FROM AND AGAINST ANY SUCH LIABILITY.
 */
-->
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
          crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"
          crossorigin="anonymous"/>
    <link rel="stylesheet" href="http://getbootstrap.com/examples/starter-template/starter-template.css"/>
    <script src="https://code.jquery.com/jquery-2.1.4.min.js" type="text/javascript"></script>
    <script src="./js/ServerSDK.js" type="text/javascript"></script>
    <script type="text/javascript">
        var discoveryUrl = '/start-discovery.php';
        var authorizationUrl = '/request-authorisation.php';
        var errorPageUrl = '/error-page.php';

        MobileConnectServerSDK.configure(discoveryUrl, authorizationUrl, errorPageUrl);
    </script>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="#">Mobile Connect Demo</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="starter-template">
        <img src='img/tick.jpg' alt='Tick' title='' border='0' align="center"/>
        <p><a name="closeButton" id="closeButton" onClick="window.close(); return false;" href='#'><em>Close Window</em></a>
        </p>
    </div>
    <h3>ID Token Response</h3>
    <p>
        The following is the ID Token response from the mobile connect authorisation service, this is to demonstrate
        that authorisation
        was successful and to document the expected response from the Mobile Connect PHP SDK.
    </p>
    <table>
        <?php
        /**
         * assume the existence of an idToken in the serialise encoded format
         */
        $idToken = unserialize(base64_decode($_GET['idToken']));

        if (!is_null($idToken)) {
            var_dump($idToken);
        }
        ?>
    </table>
</div>
</body>
</html>
