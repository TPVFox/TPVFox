# -*- coding: utf-8 -*-
# Script para leer tablas en dbf
# Con la libreria dbfread identifica los registros marcados como borrados.
# Para ejecutar directamente en terminal python
# python leerDbf1.py -f /home/solucion40/www/superoliva/datos/DBF71/albprol.dbf -i 1 -e 5000

import re
import os,argparse,sys
from dbfread import DBF,FieldParser,InvalidValue
import json
from collections import OrderedDict # Necesario para poder ordenar tal cual el JSON
from importlib import reload

# Clase para poder obtener nombre campo y dato.
class MyFieldParser(FieldParser):
    def parse(self, field, data):
        try:
            return FieldParser.parse(self, field, data)
        except ValueError:
            return InvalidValue(data)


def convert(s):
    try:
        return s.group(0).encode('ascii').decode('utf8')
    except:
        return s.group(0)

num_inicio = 0
num_final = 0

# Inicio para poder recibir parametros.
parser = argparse.ArgumentParser()
parser.add_argument("-f", "--file", help="Nombre de archivo a procesar")
parser.add_argument("-i", "--inicio", help="Variable inicio de registro")
parser.add_argument("-e", "--final", help="Variable final de registro")
args = parser.parse_args()

if args.file:
    fichero= args.file
if args.inicio:
    num_inicio= int(args.inicio)
if args.final:
    num_final= int(args.final)

# Dejo comentado valores variable fichero,num_final,num_inicio
# por si tengo que hacer pruebas desde python.
#~ num_final =100
#~ num_inicio = 99
#~ fichero = '/home/solucion40/www/superoliva/datos/DBF71/albprol.dbf'

# borrar si no es necesario
#reload(sys)
#sys.setdefaultencoding('utf-8')

db = DBF(fichero, parserclass=MyFieldParser)

Numregistros = len(db)
# print(Numregistros)
registrosEliminado = len(db.deleted)
#print(registrosEliminado)
# La pruebas es que es 0 el valor por lo que no lo voy controlar de momento.
# y de todos modos debería tener un campo en la tabla registro_importar de eliminados.

if num_final > Numregistros or num_final == 0:
    num_final = Numregistros

for i, record in enumerate(db):
    if i not in range(num_inicio, num_final + 1):
        continue

    #~ print record.items['name']
    Json = []
    for name, value in record.items():
        Nombre = str(name)
        try:
            V = re.sub(r'[\x80-\xff]+', convert, value.decode('cp1252'))
            V = re.sub(r'[\x00-\x1f\x7f-\x9f]', '', V)
        except:
            V = re.sub(r'[\x80-\xff]+', convert, str(value))
            V = re.sub(r'[\x00-\x1f\x7f-\x9f]', '', V)
        Valor =V.strip()

        textoJson = [Nombre, str(Valor)]
        Json.append(textoJson)

    #print (Json)

    resultado = json.dumps(OrderedDict(Json))
    #~ resultado = simplejson.dumps(dict(Json)))
    print(resultado)
