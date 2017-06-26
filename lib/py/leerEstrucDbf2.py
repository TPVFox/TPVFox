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
	if tipo == "C": # Caracter
		tmp = "%s VARCHAR(%s)" % (campo.name, campo.length + 1)
		columnas.append(tmp)
	elif tipo == "N": # Numerico.
	   tmp = "%s NUMERIC(%s, %s)" % (campo.name, campo.length, campo.decimal_count)
	   columnas.append(tmp)
	elif tipo == "D": # Fecha.
		tmp = "%s DATE" % (campo.name,)
		columnas.append(tmp)
	elif tipo == "L": # Logico.
		tmp = "%s LOGICO" % (campo.name,)
		columnas.append(tmp)
	else:
		raise NotImplementedError("Tipo %s no implementado" % tipo)
	print tmp
