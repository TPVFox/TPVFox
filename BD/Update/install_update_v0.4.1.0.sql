CREATE TABLE dispositivos (  
    idDispositivo INT AUTO_INCREMENT PRIMARY KEY,  
    nombre VARCHAR(100) NOT NULL,  
    ubicacion VARCHAR(100),  
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'  
);  
  
CREATE TABLE temperaturas (  
    idTemperatura INT AUTO_INCREMENT PRIMARY KEY,  
    idDispositivo INT NOT NULL,  
    temperatura DECIMAL(5,2) NOT NULL,  
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,  
    idUsuario INT,  
    FOREIGN KEY (idDispositivo) REFERENCES dispositivos(idDispositivo)  
);