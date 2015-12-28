var malicia = require('../Malicia'),
    app = malicia.app,
    orm = require('../libs/Database');
    backendLayoutNotConnected = '../core/ihm/layout/notConnected',
    backendLayoutError = '../core/ihm/layout/error',
    backendLayoutConnected = '../core/ihm/layout/connected',
    viewDir = '../core/cms/view/';

function authenticate(name, pass, fn) {
    if (!module.parent) console.log('authenticating %s:%s', name, pass);
    orm.model('User').find({ where:{login:name} }).success(function (user) {
        if (!user) return fn(new Error('cannot find user'));
        return fn(null, user);
        /*if (passwordHash.verify(pass, user.password)) {
            return fn(null, user);
        } else {
            fn(new Error('invalid password'));
        }*/
    }).error(function (error) {
        console.log('error : ' + error);
        return fn(new Error('cannot find user'));
    });
}

var Backend = function() {

    // Session-persisted message middleware
    app.use(function (req, res, next) {
        var err = req.session.error
            , msg = req.session.success;
        delete req.session.error;
        delete req.session.success;
        res.locals.message = '';
        if (err) res.locals.message = '<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">×</button>' + err + '</div>';
        if (msg) res.locals.message = '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">×</button>' + msg + '</div>';
        res.locals.baseurl = 'lol';
        next();
    });

    app.all('/admin*',function (req, res, next) {

        res.locals.title = 'Malicia CMS';
        next();
    });
    app.get('/admin', function (req, res) {
        console.log('admin !!!');
        if (!req.session.user) {
            res.redirect('/admin/login');
        } else {
            res.redirect('/admin/home');
        }
    });

    app.get('/admin/logout', function (req, res) {
        req.session.destroy(function () {
            res.redirect('/admin');
        });
    });

    app.get('/admin/login', function (req, res) {
        if (req.session.user) {
            res.redirect('/admin/home');
        }
        res.render(viewDir + 'login', {layout: backendLayoutNotConnected});
    });

    app.post('/admin/login', function (req, res) {
        console.log('/admin/login !!!');
        authenticate(req.body.username, req.body.password, function (err, user) {
            if (user) {
                console.log('Autentification réussie');
    // Regenerate session when signing in
    // to prevent fixation
                req.session.regenerate(function () {
                    req.session.user = user;
                    res.redirect('/admin/home');
                });
            } else {
                console.log('Autentification fail');
                req.session.error = 'Authentication failed, please check your '
                    + ' username and password.'
                    + ' (use "tj" and "foobar")';
                res.redirect('/admin/login');
            }
        });
    });

    app.get('/admin/home', function(req, res){
        res.render(viewDir +  'home', {layout: backendLayoutConnected, LeftMenuAdmin: [{name: 'home', link: '/admin/home', icone:'icon-home'}]});
    });

    app.get('/admin*',  function(req, res){
        res.render(viewDir +  'error', {layout: backendLayoutError});
    });
}

module.exports = Backend;
