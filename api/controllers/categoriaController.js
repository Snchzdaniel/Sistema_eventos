// api/controllers/categoriaController.js
const Categoria = require('../models/Categoria');

exports.getAllCategorias = async (req, res) => {
  try {
    const categorias = await Categoria.getAll();
    res.status(200).json({
      status: 'success',
      data: categorias
    });
  } catch (error) {
    res.status(500).json({
      status: 'error',
      message: 'Error al obtener categorías',
      error: error.message
    });
  }
};

exports.getCategoria = async (req, res) => {
  try {
    const categoria = await Categoria.getById(req.params.id);
    if (!categoria) {
      return res.status(404).json({
        status: 'error',
        message: 'Categoría no encontrada'
      });
    }
    res.status(200).json({
      status: 'success',
      data: categoria
    });
  } catch (error) {
    res.status(500).json({
      status: 'error',
      message: 'Error al obtener la categoría',
      error: error.message
    });
  }
};

exports.createCategoria = async (req, res) => {
  try {
    if (!req.body.name) {
      return res.status(400).json({
        status: 'error',
        message: 'El nombre de la categoría es requerido'
      });
    }
    
    const newCategoria = await Categoria.create(req.body);
    res.status(201).json({
      status: 'success',
      data: newCategoria
    });
  } catch (error) {
    res.status(500).json({
      status: 'error',
      message: 'Error al crear la categoría',
      error: error.message
    });
  }
};

exports.updateCategoria = async (req, res) => {
  try {
    if (!req.body.name) {
      return res.status(400).json({
        status: 'error',
        message: 'El nombre de la categoría es requerido'
      });
    }
    
    const updated = await Categoria.update(req.params.id, req.body);
    if (!updated) {
      return res.status(404).json({
        status: 'error',
        message: 'Categoría no encontrada'
      });
    }
    
    const categoria = await Categoria.getById(req.params.id);
    res.status(200).json({
      status: 'success',
      data: categoria
    });
  } catch (error) {
    res.status(500).json({
      status: 'error',
      message: 'Error al actualizar la categoría',
      error: error.message
    });
  }
};

exports.deleteCategoria = async (req, res) => {
  try {
    const deleted = await Categoria.delete(req.params.id);
    if (!deleted) {
      return res.status(404).json({
        status: 'error',
        message: 'Categoría no encontrada'
      });
    }
    
    res.status(200).json({
      status: 'success',
      message: 'Categoría eliminada correctamente'
    });
  } catch (error) {
    res.status(500).json({
      status: 'error',
      message: 'Error al eliminar la categoría',
      error: error.message
    });
  }
};