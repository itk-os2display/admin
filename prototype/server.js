// server.js
// load the things we need
var express = require('express');
var app = express();

// set the view engine to ejs
app.set('view engine', 'ejs');

// use res.render to load up an ejs view file

// index page 
app.get('/', function(req, res) {
    res.render('pages/index');
});

// create-new-slide page
app.get('/create-new-slide', function(req, res) {
    res.render('pages/create-new-slide');
});

// edit-slide page
app.get('/edit-slide', function(req, res) {
    res.render('pages/edit-slide');
});

app.use(express.static(__dirname + '/'));

app.listen(8080);
console.log('8080 is the magic port');