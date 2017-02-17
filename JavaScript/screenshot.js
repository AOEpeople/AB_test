var page = require('webpage').create(),
    system = require('system'),
    address, output;

address = system.args[1];
output = system.args[2];

page.viewportSize = { width: 1024, height: 768 };

page.open(address, function(status) {
  if (status !== 'success') {
    console.log('Unable to load the address!');
    phantom.exit(1);
  } else {
    window.setTimeout(function () {
      page.viewportSize = { width: 2000, height: 2000 };
      page.evaluate(function() {
        var style = document.createElement('style'),
            text = document.createTextNode('body { background: #fff }');
        style.setAttribute('type', 'text/css');
        style.appendChild(text);
        document.head.insertBefore(style, document.head.firstChild);
      });
      page.render(output);
      phantom.exit();
    }, 5000);
  }
});
