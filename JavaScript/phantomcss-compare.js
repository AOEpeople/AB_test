var fs = require('fs');
var PATH = fs.workingDirectory;
phantomcss = require(PATH +"/node_modules/phantomcss/phantomcss.js");
phantom.casperTest = true;


var casper = require("casper").create(),
    viewportSizes = [
        /*[320,480],
         [320,568],
         [600,1024],
         [1024,768],
         [1280,800],*/
        [1440,900]
    ],
    Urls = [casper.cli.args[0], casper.cli.args[1]],
    urlParts = Urls[0].replace(/\//gi, '^').replace(/^https?:\^+/, '').split('^'),
    saveDir = urlParts[0],
    baseFileName = urlParts.slice(1).join('-').replace(/\-$/, '');

    if (baseFileName == ''){
        baseFileName = 'index';
    }

phantomcss.init({
                    screenshotRoot:PATH + '/Tmp/Screenshots',
                    failedComparisonsRoot:PATH + '/Tmp/Screenshots/failures/' + saveDir,
                    libraryRoot:PATH + '/node_modules/phantomcss/',
                    addIteratorToImage: false
                });

casper.start();
casper.each(viewportSizes, function(self, viewportSize, i) {

    // set two vars for the viewport height and width as we loop through each item in the viewport array
    var width = viewportSize[0],
        height = viewportSize[1];

    // give some time for the page to load
    casper.wait(10000, function() {

        // set the viewport to the desired height and width
        this.viewport(width, height);

        casper.eachThen(Urls, function(response) {
            this.thenOpen(response.data, function(response) {
                casper.evaluate(function() {
                    var style = document.createElement('style'),
                        text = document.createTextNode('body { background: #fff }');
                    style.setAttribute('type', 'text/css');
                    style.appendChild(text);
                    document.head.insertBefore(style, document.head.firstChild);
                });

                var FPfilename = saveDir + '/' + baseFileName + '-fullpage-' + width;
                phantomcss.screenshot('body', FPfilename);
            });
        });

        casper.then(function(){
            phantomcss.compareSession();
        });
    });
});

casper.run(function() {
    this.echo('Finished captures for ' + casper.cli.args[0] + ' and ' + casper.cli.args[1]).exit();
});
