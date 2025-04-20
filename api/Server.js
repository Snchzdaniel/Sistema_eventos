const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');

// Importar rutas
const categoriaRoutes = require('./routes/categoriaRoutes');
const eventoRoutes = require('./routes/eventoRoutes');
const ponenteRoutes = require('./routes/ponenteRoutes');
const participanteRoutes = require('./routes/participanteRoutes');
const registroRoutes = require('./routes/registroRoutes');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Rutas API
app.use('/api/categorias', categoriaRoutes);
app.use('/api/eventos', eventoRoutes);
app.use('/api/ponentes', ponenteRoutes);
app.use('/api/participantes', participanteRoutes);
app.use('/api/registros', registroRoutes);

// Ruta principal
app.get('/', (req, res) => {
  res.json({ message: 'Bienvenido a la API de Gestión de Eventos Académicos' });
});

// Manejador de errores
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({
    status: 'error',
    message: 'Ocurrió un error en el servidor',
    error: process.env.NODE_ENV === 'development' ? err.message : {}
  });
});

// Iniciar servidor
app.listen(PORT, () => {
  console.log(`Servidor corriendo en el puerto ${PORT}`);
});

module.exports = app;