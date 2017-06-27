# -*- coding: utf-8 -*-
# Script para leer tablas en dbf
# Con la libreria dbfread identifica los registros marcados como borrados.
import os,argparse
from dbfread import DBF,FieldParser,InvalidValue
import simplejson,json
from collections import OrderedDict # Necesario para poder ordenar tal cual el JSON
# Inicio para poder recibir parametros.
parser = argparse.ArgumentParser()
parser.add_argument("-f", "--file", help="Nombre de archivo a procesar")
parser.add_argument("-i", "--inicio", help="Variable inicio de registro")
parser.add_argument("-e", "--final", help="Variable final de registro")
args = parser.parse_args()
if args.file:
    fichero= args.file
if args.inicio:
    num_inicio= args.inicio
    
if args.final:
    num_final= args.final
    
  
# Clase para poder obtener nombre campo y dato.    
class MyFieldParser(FieldParser):
    def parse(self, field, data):
        try:
            return FieldParser.parse(self, field, data)
        except ValueError:
            return InvalidValue(data)


# Dejo comentado valores variable fichero,num_final,num_inicio
# por si tengo que hacer pruebas desde python.
num_final =100
num_inicio = 99
#~ fichero = '/home/solucion40/www/superoliva/datos/DBF71/albprol.dbf'

db = DBF(fichero, parserclass=MyFieldParser)
l = 0
Numregistros = len(db)
#~ print Numregistros
registrosEliminado = len(db.deleted)
#~ print registrosEliminado
x = 0
if int(num_final) <= Numregistros:
    for i, record in enumerate(db):
         if i<= int(num_final) and i>= int(num_inicio):
             #~ print record.items['name']
             registro = []  # Creamos una lista
             Json = []
             y=0 
             for name, value in record.items():
                y = y +2
                Nombre = str(name)
                V = str(value)
                Valor =  unicode(V, "cp1252")
                textoJson =[Nombre,Valor]
                registro[y:2] = Nombre,Valor
                Json.append(textoJson)
             #~ print Json
             #
             resultado = json.dumps(OrderedDict(Json))
             #~ resultado = simplejson.dumps(dict(Json)))
             print resultado
