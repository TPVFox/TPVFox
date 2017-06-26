#! /usr/bin/env python
# -*- coding: cp1252 -*-
# argparse - Necesario para obtener parametros.
import os,dbf,argparse
from dbfpy.dbf import Dbf
# Inicio para poder enviar parametros.
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
	


ord(u'\x03')  # Lo encontre para poner codigo ascii ( ñ)
# fichero = '/home/solucion40/www/superoliva/datos/DBF71/albprol.dbf'
arch = Dbf(fichero)
#~ rango = range(min(10000,len(arch)))
rango = num_final
print rango
print len(arch)
if int(num_final) <= len(arch):
	print 'Entro'
	for ver in range(int(num_inicio),int(num_final)):
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

	
	
