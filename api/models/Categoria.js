// api/models/Categoria.js
const db = require('../config/db');
const { v4: uuidv4 } = require('uuid');

class Categoria {
  static async getAll() {
    try {
      const [rows] = await db.query('SELECT * FROM EVENT_CATEGORIES');
      return rows;
    } catch (error) {
      throw error;
    }
  }

  static async getById(id) {
    try {
      const [rows] = await db.query('SELECT * FROM EVENT_CATEGORIES WHERE id = ?', [id]);
      return rows[0];
    } catch (error) {
      throw error;
    }
  }

  static async getByCategoryId(categoryId) {
    try {
      const [rows] = await db.query('SELECT * FROM EVENT_CATEGORIES WHERE category_id = ?', [categoryId]);
      return rows[0];
    } catch (error) {
      throw error;
    }
  }

  static async create(categoria) {
    try {
      const categoryId = categoria.category_id || `CAT${uuidv4().substring(0, 6)}`;
      const [result] = await db.query(
        'INSERT INTO EVENT_CATEGORIES (category_id, name, description) VALUES (?, ?, ?)',
        [categoryId, categoria.name, categoria.description]
      );
      return { id: result.insertId, category_id: categoryId, ...categoria };
    } catch (error) {
      throw error;
    }
  }

  static async update(id, categoria) {
    try {
      const [result] = await db.query(
        'UPDATE EVENT_CATEGORIES SET name = ?, description = ? WHERE id = ?',
        [categoria.name, categoria.description, id]
      );
      return result.affectedRows > 0;
    } catch (error) {
      throw error;
    }
  }

  static async delete(id) {
    try {
      const [result] = await db.query('DELETE FROM EVENT_CATEGORIES WHERE id = ?', [id]);
      return result.affectedRows > 0;
    } catch (error) {
      throw error;
    }
  }
}

module.exports = Categoria;