# -*- coding: utf-8 -*-
# Script para leer tablas en dbf
# Con la libreria dbfread identifica los registros marcados como borrados.
# Para ejecutar directamente en terminal python
# python leerDbf1.py -f /home/solucion40/www/superoliva/datos/DBF71/albprol.dbf -i 1 -e 5000
import os,argparse,sys
from dbfread import DBF,FieldParser,InvalidValue
import json
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
#~ num_final =100
#~ num_inicio = 99
#~ fichero = '/home/solucion40/www/superoliva/datos/DBF71/albprol.dbf'
reload(sys)
sys.setdefaultencoding('utf-8')
db = DBF(fichero, parserclass=MyFieldParser)
l = 0
Numregistros = len(db)
#~ print Numregistros
registrosEliminado = len(db.deleted)
#~ print registrosEliminado
x = 0
if int(num_final) > Numregistros:
    num_final = Numregistros

for i, record in enumerate(db):
    if i > int(num_final) or i < int(num_inicio):
        continue

    #~ print record.items['name']
    registro = []  # Creamos una lista
    Json = []
    y=0
    for name, value in record.items():
        y = y +2
        Nombre = str(name)
        V = str(value)

        # lin. valor para provocar error lectura
        #~ Valor =  unicode(V, "UTF8")

        try:
            Valor =  unicode(V, "UTF8")
        except UnicodeDecodeError:
            #~ print 'Error entro execetp'
            Valor = unicode(V, "cp1252")

        textoJson =[Nombre,str(Valor)]
        registro[y:2] = Nombre,Valor
        Json.append(textoJson)

    #~ print Json

    resultado = json.dumps(OrderedDict(Json))
    #~ resultado = simplejson.dumps(dict(Json)))
    print resultado
