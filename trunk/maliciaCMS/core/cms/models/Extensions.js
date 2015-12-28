/**
 * User model
 */

//Getting the orm instance
var orm = require("../libs/Database")
    , Seq = orm.Seq();


//Creating our module
var Extensions = {
    model:{
        name: {
            type:Seq.STRING,
            unique:true
        }
    }
}

module.exports = Extensions;