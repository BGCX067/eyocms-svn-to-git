var Database = require('./Database');

/**
 * Liste des models
 * Singleton
 */
var Models = function Models() {
    var modelList = {};

    this.addAndImport = function(modelName, modelPath) {
        console.dir(Database);
        this.add(modelName, Database.importModel(modelPath));
    };

    this.add = function (modelName, modelObject) {
        if (!modelList[modelName]) {
            modelList[modelName] = modelObject;
        }
    };

    this.remove = function (modelName) {
        if (modelList[modelName]) {
            delete modelList[modelName];
        }
    };

    this.get = function (modelName) {
        return modelList[modelName];
    };

    if (Models.caller != Models.getInstance) {
        throw new Error("This object cannot be instanciated");
    }
};


Models.instance = null;

/**
 * Models getInstance definition
 * @return Models class
 */
Models.getInstance = function () {
    if (this.instance === null) {
        this.instance = new Models();
    }
    return this.instance;
}

module.exports = Models.getInstance();