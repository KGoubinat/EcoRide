const { Sequelize, DataTypes } = require('sequelize');
const sequelize = new Sequelize('ecoride', 'root', 'nouveau_mot_de_passe', {
    host: 'localhost',
    dialect: 'mysql',
  });
  

const users = sequelize.define('users', {
    name: {
        type: DataTypes.STRING,
        allowNull: false
    },
    email: {
        type: DataTypes.STRING,
        allowNull: false,
        unique: true
    },
    password: {
        type: DataTypes.STRING,
        allowNull: false
    },
    role: {
        type: DataTypes.ENUM('admin', 'employee', 'user'),
        defaultValue: 'user' // Les utilisateurs lambda ont ce rôle par défaut
    }
});

module.exports = { users };
