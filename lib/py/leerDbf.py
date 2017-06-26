#! /usr/bin/env python
# -*- coding: cp1252 -*-
# argparse - Necesario para obtener parametros.
import os,dbf,argparse
from dbfpy.dbf import Dbf
# Inicio para poder enviar parametros.
parser = argparse.ArgumentParser()
parser.add_argument("-f", "--file", help="Nombre de archivo a procesar")
args = parser.parse_args()
 
if args.file:
    fichero= args.file

#

ord(u'\x03')  # Lo encontre para poner codigo ascii ( ñ)
#~ fichero = '/home/solucion40/www/superoliva/datos/DBF71/albprol.dbf'
arch = Dbf(fichero)
rango = range(min(10000,len(arch)))
for ver in rango:
	#muestro por terminal los datos segun 1 rango
	x = arch[ver]
	#datos que obtengo
	numalb = x['NNUMALB']
	ref = 	x['CREF']
	det = str(x['CDETALLE'])
	detalles = det
	# s = re.findall(detalles.encode('cp1250')
	precio =  x['NPREDIV']
	jSON= '{"numalb":'+str(numalb)+',"ref":"'+ref+'","detalles":"'+detalles+'","precio":'+str(precio)+'}'
	print jSON

	
	
