/**
 * Database Connection
 */
var HashMap = require('../../../lib/HashMap'),
    fs = require('fs')
    utils = require('./Utils'),
    path = require('path');


var models = {},
    relationships = {};

var singleton = function singleton(){
    var Sequelize = require("sequelize");
    var sequelize = null;
    var modelsPath = "";
    this.setup = function (options){
        sequelize = new Sequelize(options.DATABASE.DATABASE, options.DATABASE.USER, options.DATABASE.PASS, {
            host:options.DATABASE.HOST,
            port:options.DATABASE.PORT,
            logging:options.logging,
            dialect:'mysql',
            maxConcurrentQueries:100
        });
        init();
    }

    this.model = function (name){
        return models[name];
    }

    this.addModel = function (file) {
        var object = require(file);
        var options = object.options || {};
        var modelName = path.basename(file, '.js');
        if (!models[modelName]) {
            models[modelName] = sequelize.define(modelName, object.model, options);
        }
    }

    this.addModelsOfDir = function(dir) {
        var listFile = fs.readdirSync(dir);

        if (Array.isArray(listFile)) {
            listFile.forEach(function(file){
                this.addModel(dir + file);
            }, this);
        }
    }

    this.Seq = function (){
        return Sequelize;
    }

    this.sync = function() {
        sequelize.sync();
    }

    function init() {
    }

    if(singleton.caller != singleton.getInstance){
        throw new Error("This object cannot be instanciated");
    }
}

singleton.instance = null;

singleton.getInstance = function(){
    if(this.instance === null){
        this.instance = new singleton();
    }
    return this.instance;
}

module.exports = singleton.getInstance();