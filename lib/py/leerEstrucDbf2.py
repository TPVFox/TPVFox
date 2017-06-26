# -*- coding: utf-8 -*-
import os,argparse
from dbfread import DBF

# Inicio para poder enviar parametros.
parser = argparse.ArgumentParser()
parser.add_argument("-f", "--file", help="Nombre de archivo a procesar")
args = parser.parse_args()
if args.file:
    fichero= args.file
#~ print fichero
# Generamos estructura de DBF
#~ strcampos = dbf.structure(fichero, field=None) 
#~ print strcampos




db = DBF(fichero)
columnas = []
campos = db.fields
for campo in campos:
	tipo = campo.type
	decimal = 0
	nombcampo = campo.name
	tipocampo = tipo
	longit = campo.length 
	if tipo == "N":
		decimal = campo.decimal_count
	
	jSON= '{"campo":"'+nombcampo+'","tipo":"'+tipocampo+'","longitud":'+str(longit)+'","decimal":'+decimal+'}'
	print jSON
	

