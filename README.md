# Readme

This document describes what you'll need and how to run the automated comparison of LIVE vs. Devbox

## Requirements to run the tests

Make sure the VagrantBox is up and running, and the .local URLs are all reachable.

PHP 5.3+ has to be installed and available on CLI.

For the screenshot comparison we have the following binary requirements:

- [PhantomCSS](https://github.com/Huddle/PhantomCSS)
- CasperJS 1.1-Beta (Important! Has to be the beta)
- PhantomJS

## Automated FE Test

Simply run:
-----------------------------------------
npm install
export PHANTOMJS_EXECUTABLE=${WORKSPACE}/node_modules/phantomcss/node_modules/phantomjs/bin/phantomjs;
php Classes/Cli/Dispatcher.php -c ABTest -a compareFrontend --url=http://dummy-domain.com --log-level=1 --max-fail-count=1000
-----------------------------------------

To let the defined URL be tested it HAS to be defined in config.json file:
e.g.

  "dummy-domain.com":{
    "compareDomain":"www.deploy.dummy-domain.aoe.host",
    "comparisonType":[
      "frontend",
      "source"
    ]
  },
  "www.dummy2.de":{
    "compareDomain":"www.deploy.dummy2.aoe.host",
    "comparisonType":[
      "frontend",
      "source"
    ]
  }  

The config.json file must be located in the project's repository folder

==> Tests/AB/config.json


## Todo

The following describes what is left to be done:

- We need to use the latest backup from Prod. 
- Screenshot comparison is possible with PhantomCSS, but not yet done as the Text Comparison is faster and for now
good enough to find the differences. But it might be necessary to implement this
- make sure the manual testplan (Manual_Testing.md) is executed manually by a tester as some things we cannot automatically
test
