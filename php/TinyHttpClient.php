<?php 
/*
File: TinyHttpClient.php
Date: 7/6/2014
Version 1.3.4
Author: HenryRanch LLC

LICENSE:
============
Copyright (c) 2009-2014, Henry Ranch LLC. All rights reserved. http://www.henryranch.net


TinyHttpClient is governed by the following license and is not licensed for use outside of 
the SyndicatePress Wordpress plugin.  By downloading or using this software,  you
 agree to all the following: 
 
 YOU WILL NOT USE OR COPY THIS SOFTWARE OUTSIDE OF THE SYNDICATE PRESS
 WORDPRESS PLUGIN.
 
 THIS SOFTWARE IS PROVIDED BY HENRY RANCH LLC `AS IS' AND ANY EXPRESS
 OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 ARE DISCLAIMED.  IN NO EVENT SHALL HENRY RANCH LLC OR ANY OF THE AUTHORS
 BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT 
 OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.   
  
 IF YOU WOULD LIKE TO USE THIS SOFTWARE IN YOUR OWN APPLICATION, 
YOU MAY REQUEST A LICENSE TO DO SO FROM THE AUTHOR.
 
 YOU AGREE THAT YOU WILL NOT USE THIS SOFTWARE TO ACCESS, DISPLAY
OR AGGREGATE CONTENT IN A MANNER THAT VIOLATES THE COPYRIGHT, 
INTELLECTUAL PROPERTY RIGHTS OR TRADEMARK OF ANY ENTITY.  
 
 HENRYRANCH LLC SHALL NOT BE LIABLE FOR ANY COPYRIGHT INFRINGEMENT CLAIMS
 MADE THROUGH YOUR USE OF THIS SOFTWARE.  YOU AGREE TO INDEMNIFY, PROTECT 
 AND SHIELD HENRYRANCH LLC FROM ALL LEGAL JUDGEMENTS, FEES, COSTS AND/OR 
 ANY ASSOCIATED FEES THAT MAY RESULT OUT OF YOUR USE OF THIS SOFTWARE.
 YOU AGREE THAT YOU ARE SOLELY RESPONSIBLE FOR YOUR USE OF THIS SOFTWARE
 AND SHALL FOREVER HOLD HENRYRANCH LLC HARMLESS IN ALL MATTERS.
 
 ANY INSTALLATION OR USE OF THIS SOFTWARE MEANS THAT YOU ACCEPT AND AGREE 
 TO ABIDE BY ALL OF THE TERMS OF THIS LICENSE AGREEMENT.
*/

class TinyHttpClient 
{
    var $debug = false;
    var $userAgent = "TinyHttpClient/1.3.4";

   /*
        Create a GET request header for the given host and filename.  If authorization is required, then it must be the standard HTTP 1.0 Basic Authentication compliant string.
        @param $host  - the host name of the remote server
        @param $filename - the filename of the resource that resides ont he remote server.  Must start with a '/'.  Append key=value pairs in GET format to the end of the resource URL
        @param $authorization - the HTTP 1.0 compliant Basic Authentication digest string
        @returns The GET request header
        */
    function generateGetRequest($host, $filename, $authorization, $userAgent="User-Agent: TinyHttpClient/1.3.4\r\n")
    {
        $request = "GET $filename HTTP/1.0\r\n" .
        "Host: $host\r\n" .
        $authorization . 
        //"User-Agent: TinyHttpClient/1.1\r\n" .
        //"User-Agent: Mozilla/5.001 (windows; U; NT4.0; en-US; rv:1.0) Gecko/25250101\r\n" .
	"User-Agent: " . $userAgent . "\r\n" .
        "Connection: close\r\n" .
        "\r\n";
        return $request;
    }

   /*
        Create a POST request header for the given host and filename.  If authorization is required, then it must be the standard HTTP 1.0 Basic Authentication compliant string.
        @param $host  - the host name of the remote server
        @param $filename - the filename of the resource that resides ont he remote server.  Must start with a '/'
        @param $authorization - the HTTP 1.0 compliant Basic Authentication digest string
        @param $from - POST requests require a 'from' email address that is on your host server domain
        @param $data - the POST data (key value pairs)
        @returns The POST request header
        */
    function generatePostRequest($host, $filename, $authorization, $from, $data, $userAgent="User-Agent: TinyHttpClient/1.3.4\r\n")
    {
        $request = "POST $filename HTTP/1.0\r\n" .
        "Host: $host\r\n" .
        $authorization .
        "Connection: close\r\n" .
        "From: $from\r\n" .
        //"User-Agent: TinyHttpClient/1.1\r\n" .
        //"User-Agent: Mozilla/5.001 (windows; U; NT4.0; en-US; rv:1.0) Gecko/25250101\r\n" .
	"User-Agent: " . $userAgent . "\r\n" .
        "Content-Type: application/x-www-form-urlencoded\r\n" .
        "Content-Length: " . strlen($data) . "\r\n" .
        "\r\n" .
        $data . "\r\n";
        return $request;
    }

   /*
        Create a POST request header for the given host and filename.  If authorization is required, then it must be the standard HTTP 1.0 Basic Authentication compliant string.
        @param $host  - the host name of the remote server
        @param $remoteFilename - the filename of the resource that resides ont he remote server.  Must start with a '/'
        @param $usernameColonPassword - the username and password if using Basic Authentication to access the URL (i.e. bobUsername:bobPassword).  
                                                                      If no Basic Authentication is required, simply pass an empty string.
        @param $receiveBufferSize - the size (in bytes) of the receive buffer.  This can affect performance.  A good starting place is 2048.  You should determine this value based on your server environment.
        @param $mode - either 'get' or 'post'
        @param $fromEmail - only required if using $mode=post
        @param $postData - the key value pairs of data.  only required if using $mode=post
        @param $localFilename - the filename, local to your server to store the downloaded URL contents in.
                                                    Set this value to "" (empty string) if you want the URL contents returned from this function as a string.
        @returns URL contents or an error msg.
        */
    function getRemoteFile($host, $port, $remoteFilename, $usernameColonPassword, $receiveBufferSize, $mode, $fromEmail, $postData, $localFilename) 
    {
        $fileData = "";

        if($remoteFilename == "")
            $remoteFilename = "/";

        if($port == -1)
            $port = 80;

        $timeout = 30;
        $sHandle = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$sHandle) 
        {
            return "<font color=red><SOCKET ERROR $errno: $errstr</font><br>";	
        }
            
        $authorization = "";
        if($usernameColonPassword != "")
        {
            $authorization = "Authorization: Basic " . base64_encode($usernameColonPassword) . "\r\n";
        }

        if($mode == "get")
        {
            $request = $this->generateGetRequest($host, $remoteFilename, $authorization, $this->userAgent);
        }
        else if($mode == "post")
        {
            $request = $this->generatePostRequest($host, $remoteFilename, $authorization, $fromEmail, $postData, $this->userAgent);
        }
        if($this->debug)
            print "Sending request string:<br>$request<br><br>";

        fwrite($sHandle, $request);

        $data = "";
        $buf = "";
        do
        {
            $buf = fread($sHandle, $receiveBufferSize);
            if($buf != "")
            {
                if($this->debug) print "READ: <br>$buf<br><br>";
                $data .= $buf;
            }
        } while($buf != "");
        
        fclose($sHandle);
        $dataArray = explode("\r\n\r\n", $data);
        $numElements = count($dataArray);
        $body = "";
        $header = $dataArray[0];
        for($i = 1; $i <= $numElements; $i++)
        {
            $body .= $dataArray[$i];
            if($this->debug) print "body loop $i:<br>$body<br> ";
        }
        if($this->debug)
            print "<br><br>dataArray len is ".count($dataArray).".<br><br>".
                "header is:<br>".$header."<br><br>".
                "body is:<br>".$body."<br>";
        
        if(strpos($header, "HTTP/1.1 301") !== false)
        {
            //print "Header contains move message when requesting $remoteFile<br>";
            $headerArray = explode("\r\n", $header);
            $locationUrl = "not_found";
            $numElements = count($headerArray);
            for($i = 0; $i < $numElements; $i++)
            {
                $headerLine = $headerArray[$i];
                if(strpos($headerLine, "Location:") !== false)
                {
                    $locationLine = explode(" ", $headerLine);
                    $locationUrl = $locationLine[1];
                }
            }
            return "HTTP-301_MOVED_TO:".$locationUrl;
        }
        if($localFilename == "")
        {
            return $body;
        }
        else
        {
            if($this->debug) print "writing to local file: $localFilename<br>";
            $fHandle = fopen($localFilename, 'w+');
            if($fHandle) 
            {
                fwrite($fHandle, $body);
                fclose($fHandle);
                return "remote file saved to: <a href=$localFilename target=_blank>$localFilename</a><br>";
            }
            else
            {
                return "<font color=red><FILE ERROR cannot write to file: $localFilename</font><br>";	
            }
            
        }
    }
}
?>
