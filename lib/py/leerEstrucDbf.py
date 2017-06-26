# -*- coding: utf-8 -*-
import os,dbf,argparse
# Inicio para poder enviar parametros.
parser = argparse.ArgumentParser()
parser.add_argument("-f", "--file", help="Nombre de archivo a procesar")
args = parser.parse_args()
if args.file:
    fichero= args.file
#~ print fichero
# Generamos estructura de DBF
strcampos = dbf.structure(fichero, field=None) 
print strcampos
