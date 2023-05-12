# moodle-quiz_datawarehouse
![](https://github.com/lucaboesch/moodle-quiz_datawarehouse/actions/workflows/moodle-plugin-ci.yml/badge.svg)


[![Moodle Plugin CI](https://github.com/moodle-an-hochschulen/moodle-theme_boost_union/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)](https://github.com/moodle-an-hochschulen/moodle-theme_boost_union/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Amaster)

My MSc Thesis project

The quiz database report is a three fold plug-in consisting of a admin backend, a frontend quiz report and backend web services.  
In the site administration, administrators define queries that selected user can run.  
The user interface to run those queries is found under the quiz reports.  
Then, the results can be fetched through web service.  
This allows for an automated regular fetching, in order that the data can be fed to a data warehouse.

To set everything up correctly, the following steps have to be made:

*   The site must have Web services enabled.
*   The site must have a Web services protocol (preferrably REST) enabled.
*   The capability "moodle/webservice:createtoken" should be allowed to the "Authenticated user" role in order that it's possible to generate a security key.
*   To fetch the provided data<sup><small>&#42;</small></sup>, a user with a newly created role "Data warehouse webservice user", based on no other role or archetype, and granted the "quiz/datawarehouse:view", "quiz/datawarehouse:viewfiles", as well as "webservice/rest:use" on "System" and "Course" level has to be used. She/he has to include her/his token ("Key") retrieved under Security keys in the call.
*   The administrator defines queries in the site administration page and give them a distinguishable name.
*   The capability "quiz/datawarehouse:view" should be allowed to the user that should be able to run a query and to generate a data set out of a quiz.
*   The web service Quiz report datawarehouse functionalities must have the "Can download files" option checked.
*   To use the web service the token ("Key") retrieved under Security keys has to be included in the call <sup><small>&#42;&#42;</small></sup>.

<sup><small>&#42;</small></sup> A call can be made with `curl "<host>/webservice/rest/server.php?wstoken=<token>&wsfunction=quiz_datawarehouse_get_all_files&moodlewsrestformat=json"`.<br/>
<sup><small>&#42;&#42;</small></sup> A call can be made with `curl "<host>/webservice/pluginfile.php/1/quiz_datawarehouse/data/<filename>&token=<token>`.

Installation
------------

Install the plugin to folder
/mod/quiz/report/datawarehouse

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins
