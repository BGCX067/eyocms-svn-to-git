/**
 * User model
 */

//Getting the orm instance
var orm = require("../libs/Database")
    , Seq = orm.Seq();


//Creating our module
var User = {
    model:{
        name:Seq.STRING,

        firstName:Seq.STRING,

        login:{
            type:Seq.STRING,
            unique:true
        },

        password:Seq.STRING,

        email:{
            type:Seq.STRING,
            validate:{
                isEmail:true
            }
        }
    }
}

module.exports = User;