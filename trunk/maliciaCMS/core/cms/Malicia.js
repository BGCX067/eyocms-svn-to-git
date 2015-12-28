var express = require('express'),
    ENV = process.env.NODE_ENV || 'development',
    http = require('http'),
    path = require('path'),
    fs = require('fs'),
    Utils = require('./libs/Utils')
    log4js = require('log4js'),
    hbs = require('hbs');

function Malicia() {
    this.app = null;
    this.logger = null;
    this.config = {};
    this.orm = null;

    if (Malicia.caller != Malicia.getInstance) {
        throw new Error("This object cannot be instanciated");
    }
};

Malicia.prototype = {
    initFolder:function () {
        Utils.mkdirIfFolderNoExist(maliciaConfDir);
        Utils.mkdirIfFolderNoExist(maliciaTmpDir);
    },

    initLogger:function() {
        try {
            var pathLog4jsConfig = path.join(maliciaConfDir, 'log4js.json');
            var stat = fs.statSync(pathLog4jsConfig);
            if (stat && stat.isFile()) {
                log4js.configure(pathLog4jsConfig);
            }
        } catch(err) {}
        global.logger = this.logger = log4js.getLogger();
    },

    initDatabase:function () {
        var optionsDb = this.config;
        optionsDb.logging = console.log;
        this.orm = require('./libs/Database');
        this.orm.setup(optionsDb);
    },

    includeModels:function(){
        try {
            this.orm.addModelsOfDir(__dirname + '/models/');
            this.orm.sync();

            //création de l'administrateur
            this.orm.model('User').create({
                login : 'Admin',
                password : 'Admin123'
            }).error(function(error) {}).success(function(user){
                console.log('Création du compte admin : Admin/Admin123');
            });

        } catch (err){}
    },

    initConf:function () {
        var self = this;
        this.config = Utils.loadConfig(maliciaConfDir);
    },

    initServer:function () {
        this.logger.info('Configuration du serveur');
        this.app = express();
        var self = this;
        this.app.configure(function () {
            self.app.set('port', self.config.PORT);
            self.app.set('view engine', 'html');
            self.app.engine('html', hbs.__express);
            self.app.set('views', maliciaDir + '/public');
            self.app.use(express.favicon());
            self.app.use(express.bodyParser());
            self.app.use(express.methodOverride());
            self.app.use(express.cookieParser('your secret here'));
            self.app.use(express.session());
            self.app.use(express.static(path.join(maliciaDir, '/public')));
            self.app.use('/admin', express.static(path.join(maliciaCoreDir, '/ihm')));
            self.app.use(log4js.connectLogger(self.logger));
        });

        this.app.configure('development', function () {
            self.app.use(express.errorHandler());
//            self.app.use(express.logger('dev'));
        });
        this.logger.info('Fin Configuration du serveur');

        this.app.get('/', function(req, res){
            res.render('../test.html');
        });

    },

    includeRouters:function() {
        var dirRouters = __dirname + '/routers/',
            listRouter = fs.readdirSync(dirRouters);

        listRouter.forEach(function(item){
            try {
                require(path.join(dirRouters, item))();
            } catch (err) {}
        }, this);
    },


    init:function () {
        global.maliciaDir = path.resolve(__dirname, '../../');
        global.maliciaLibDir = path.join(maliciaDir, '/lib');
        global.maliciaCoreDir = path.join(maliciaDir, '/core');
        global.maliciaExtensionDir = path.join(maliciaDir, '/ext');
        global.maliciaTmpDir = path.join(maliciaDir, '/temp');
        global.maliciaConfDir = path.join(maliciaDir, '/config');
        this.initFolder();
        this.initConf();
        this.initLogger();
        this.initServer();
        this.initDatabase();
        this.includeModels();
        this.includeRouters();
    },

    run:function () {
        var self = this;
        http.createServer(this.app).listen(self.app.get('port'), function () {
            self.logger.info("Express server listening on port " + self.app.get('port'));
        });
    }
};

Malicia.instance = null;

/**
 * Models getInstance definition
 * @return Models class
 */
Malicia.getInstance = function () {
    if (this.instance === null) {
        this.instance = new Malicia();
    }
    return this.instance;
};

module.exports = Malicia.getInstance();