var fs = require('fs'),
    ENV = process.env.NODE_ENV || 'development',
    path = require('path'),
    osSep = process.platform === 'win32' ? '\\' : '/',
    utils;

utils = {
    loadJsonFile:function (filePath) {
        var data = fs.readFileSync(filePath, 'UTF-8');

        try {
            JSON.parse(data);
        } catch (err) {
            console.log('The file ' + filePath + ' is not a json file');
            return {};
        }

        return JSON.parse(data);
    },

    mkdirIfFolderNoExist:function (path) {
        try {
            var stat = fs.statSync(path);
            if (stat && !stat.isDirectory()) {
                fs.mkdirSync(path);
            }
        } catch (err) {
            if (err.errno === 34) {
                fs.mkdirSync(path);
            }
        }
    },

    /**
     * Recursively search the directory for all JSON files, parse them
     * and trigger a callback with the contents
     */
    loadConfig:function (dir) {
        var configs = {},
            ENV = process.env.NODE_ENV || 'development';

        var pathFile = path.join(dir, '/' + ENV + '.json');
        try {
            var stat = fs.statSync(path.join(dir, '/' + ENV + '.json'));
            if (stat && stat.isFile()) {
                configs = utils.loadJsonFile(pathFile);
            }
        } catch (err){}


        return configs;
    },

    restrict:function (req, res, next) {
        if (req.session.user) {
            next();
        } else {
            req.session.error = 'Access denied!';
            res.redirect('/login');
        }
    },
    getModelFiles:function (dir) {
        var results = [];

        fs.readdirSync(dir).forEach(function(file) {
            var filePath = dir + '/' + file;
            try {
                var stat = fs.statSync(filePath);
                if (stat && stat.isDirectory()){
                    results = results.concat(utils.getModelFiles(filePath));
                } else {
                    if (/\/models\//i.test(filePath)) {
                        results.push(filePath);
                    }
                }
            } catch(err) {results.push(filePath);}
        });

        return results;
    }
};

module.exports = utils;