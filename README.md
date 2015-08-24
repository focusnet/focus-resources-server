# FOCUS Resources Server

Note: Very early development stage. Still pretty dirty.

## Description

This is the implementation of the FOCUS resources server. It is a REST server used for storing and retrieving JSON objects in the context of the FOCUS platform. 

For more information about the FOCUS project, have a look at http://www.focusnet.eu.

## Documentation

The logic behind this implementation is detailed in the FOCUS project deliverables (pending work, not public).

### General functioning

The FOCUS project aims at improving interoperability of data between stakeholders of the forest industry. The present resources server therefore acts as a repository of data that are known to follow FOCUS guidelines regarding data storing and retention. Stakeholders taking benefits of the FOCUS solution can then rely on the data provided by this resources server.

All resources are stored and transmitted as JSON objects over HTTP. This server makes sure that data are valid toward what they claim to be by validating them against JSON schemas: when a piece of data is received, it must contain at least the type of data it represents. Based on this information, the server will retrieve the corresponding JSON schema and make appropriate checks. The validation process may require to retrieve multiple schemas, possibly from multiple servers.

Resources can be retrieved, created, modified and deleted and this implementation is therefore a REST server using the GET, POST, PUT and DELETE operations for each of these tasks, respectively. 

### Data model

This server ensures that stored resources are valid in the context of the FOCUS project:

1. All resources submitted to this server must be valid JSON objects.
2. These JSON objects must at least validate against the focus-object JSON schema[ref] If this is the case, they will contain a `type` property that defines the type of resource they represent and is a URI to the corresponding JSON schema. 
3. The server will then retrieve this JSON schema and validate the object against it.

In short, any data being stored in the resource server is a valid JSON object that respects at least the focus-object JSON schema.

All JSON schemas defined by the FOCUS consortium are stored in the `focus-data-model` github repository. It is possible for other participants to define their own JSON schemas, and to let other participants of the FOCUS solution use them to instantiate new objects. 

If JSON schemas evolve, they must remain compatible with earlier versions. This garantees that older equipments and services will still be able to be understood by their newer counterparts. 

Resources may be modified over their lifetime. However, this resources server does not modify previously stored resources. Instead, it creates a new version of the resources, allowing to keep a history of the evolution of the resource and making sure that the services using these resources will not be able to retrieve a specific version of the resource.

This resources server therefore stores *samples* of data.

Data retention is also an important aspect of resources management and it is enforced by being defined in every stored resource. This information is specified as part of the focus-object JSON schema.

Beside the information that one can find in the base focus-object object, this resources server completely abstracts the content of resources. It only checks their validity. For this reason, all resources samples are stored in a flat table that only exposes some of the focus-object schema properties as their field to permit quick lookup.

### REST (CRUD) operations

In this section, we will consider that the resources server was deployed on http://data.example.org/.

````
FIXME FIXME FIXME 
NOTE: all data are accessed / created under the data/ directory.
e.g.
http://data.example.org/data/my-resource/123
and not 
http://data.example.org/my-resource/123
FIXME FIXME FIXME
````

The HTTP protocol is followed and appropriate status codes are returned.

#### Resource creation (POST)

To create a new resource, issue a POST HTTP request toward the URL of the new resource:

	HTTP POST http://data.example.org/data/my-resource/123
	
The request body will contain the JSON object representing the new resource.

It is not possible to create a resource with a specific version number, i.e. the following is not valid:

	HTTP POST http://data.example.org/data/my-resource/123/v456

This request returns:
 - `HTTP 201 Created` on success. The new resource is returned in the response body and the `Content-Location` header contains the permanent address of the resource.
 - `HTTP 409 Conflict` if the resource already exists
 - FIXME 400 bad request, ...

**FIXME is that really REST-friendly? shouldn't we access / and then the system will give us an id? see how this would impact other focus services. /// not that simple**

#### Resource retrieval (GET)

To retrieve the last version of a resource, issue a GET HTTP request toward the URL of the existing resource:

	HTTP GET http://data.example.org/data/my-resource/123

To retrieve a specific version of the same resource:

	HTTP GET http://data.example.org/data/my-resource/123/v3

This request returns:
 - `HTTP 200 FIXME` on success. The resource is returned in the response body and the `Content-Location` header contains the permanent address of the resource.
 - `HTTP 404 Not Found` if the resource already exists
 - FIXME

### Resource update (PUT)

focus-datamodel
focus-data-model

To upate an existing resource, issue a PUT HTTP request toward the URL of the existing resource:

	HTTP PUT http://data.example.org/data/my-resource/123/v456
	
The request body will contain the JSON object representing the new version of the resource.

It is not possible to update a specific version of a resource, i.e. the following is not valid:

	HTTP PUT http://data.example.org/data/my-resource/123/v456
	
This request returns:
 - `HTTP 200 FIXME` on success. The resource is returned in the response body and the `Content-Location` header contains the permanent address of the resource.
 - `HTTP 404 Not Found` if the resource already exists
 - FIXME conflict, bad request, ...
 
Note that the retention policy for this resource will enforced. In some cases, the existing resource samples may be archived without being actually deleted.

### Resource deletion (DELETE)

To delete an existing resource, issue a DELETE HTTP request toward the URL of the existing resource:

	HTTP DELETE http://data.example.org/data/my-resource/123/v456
	
This request returns:
 - `HTTP 200 FIXME` on success.
 - `HTTP 404 Not Found` if the resource already exists
 - FIXME conflict, bad request, ...
 
Note that the retention policy for this resource will enforced. In some cases, the existing resource may be archived without being actually deleted.

## Services

### get-as-bulk

TODO

### check-freshness

````
/**
 * Check for freshness resources:
 * 
 * - Read the input data from the incoming POST request. It consists of a 
 *   JSON array containing URIs of resources to check for freshness
 *   (including version number that the client end knows as the latest 
 *   version).
 *   
 * - Return a JSON array key-value pairs in the following format:
 *   REQUESTED-URI => <http-like status>
 *   
 *   	e.g.
 *   
 *   "http://server/data/test/1234/details/v43" => 304
 *   
 *   The status can be:
 *   
 *   status	HTTP description	expected client behavior
 *   	
 *   210	Content Different	client must retrieve the latest version
 *								and update its local cache
 *
 *   304	Not Modified		client has nothing to do
 *   
 *   400	Bad Request			malformed client request. Likely required to 
 *   							fix	some coding
 *   
 *   403	Forbidden			client must delete its local version (?)
 *   
 *   404	Not Found			client must delete its local version (the
 *   							resource may have been deleted since the 
 *   							last update)
 *   
 *   409	Conflict			The version on the client is more recent
 *   							than the latest version on the server. The
 *   							client should retrieve the latest version on
 *   							the server and discard its current local 
 *   							copy
 */
````

Example of input:

````
[
	"failing-test",
	"http://data.example.org/data/aaaaaaaa/v12"
]
````

And the corresponding response:

````
HTTP/1.1 200 OK
Date: Mon, 24 Aug 2015 11:59:01 GMT
Server: Apache/2.4.7 (Ubuntu)
FOCUS-API-Version: 1
FOCUS-App-Version: 0.0.1
FOCUS-App-Root: http://data.example.org/
Content-Length: 122
Content-Type: application/json

"[{\"failing-test\":400},{\"http:\\\/\\\/data.example.org\\\/data\\\/aaaaaaaa\\\/v12\":304}]"
````

i.e. the first URI is not valid and there is no newer version of the second one.


## Building and installing


config.php from config.template.php

// see INSTALL 

## Testing 

### Unit Testing

### With cURL

	$ curl -X GET -D - http://<host>/path/to/server/<resource> 
	$ curl -X GET -D - http://<host>/path/to/server/<resource>/v<version-number>
	
	$ curl -X POST -d @<file-with-json-data> -D - http://<host>/path/to/server/<resource> 
	
	$ curl -X PUT -d @<file-with-json-data> -D - http://<host>/path/to/server/<resource> 
	 
	$ curl -X DELETE -D - http://<host>/path/to/server/<resource> 
	
	
## Third-party libraries

### JSON Schema for PHP

URL: https://github.com/justinrainbow/json-schema

````
Copyright (c) 2008, Gradua Networks
Author: Bruno Prieto Reis
All rights reserved.


Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

 * Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

 * Neither the name of the Gradua Networks nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
````

## License

This software is released under the commercial-friendly and open-source MIT license.

````
The MIT License (MIT)

Copyright (c) 2015 Berner Fachhochschule (BFH) - www.bfh.ch

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
````


