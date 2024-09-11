#!/usr/bin/python
# -*- coding: utf-8 -*-
# apt-get install python-pip
# pip install mysql-connector-python
# pip install requests
# ************************ QUESTO SCRIPT NON SI ESEGUE DAL SERVER DI APIB MA DIRETTAMENTE DAL CENTRALINO ***************************
# Versione aggiornata con upload in b64 dei wav delle registrazioni SE trovati
# Una versione aggiornata di questo file si trova nel modulo asterisk del crm in attesa di fare la repo con enviroment

import mysql.connector
from pprint import pprint
import time
import requests
import json
import os
import base64

url = "https://crm.firegui.com/rest/v1/"
percorso_registrazioni = "/var/spool/asterisk/monitor/"
api_public_key = "4587529c8ec3e128f6df8514aee80bb1"
headers = {"Authorization": "Bearer "+api_public_key}

def query(type, query):
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="rubrica",
            passwd="rubrica-pass",
            database="rubrica_telefonica",
        )
        cur = conn.cursor(dictionary=True)
        cur.execute(query)


        if (type == "s"):  # Solo select
            rows = cur.fetchall()
            return rows
            conn.close()
        elif (type == "ws"):  # Write and Select Scrivo e mi aspetto un output
            conn.commit()
            return cur.fetchone()[0]
            conn.close()
        elif (type == "w"):
            print("ESEGUO QUERY: "+query)
            conn.commit()
        else:  # Tendenzialmente solo W entra qui
            return True

        conn.close()
    except mysql.connector.Error as e:
        print "\n\n****************************** Mysql ERROR ***********************************"
        print "QUERY: " + query
        print e


def send_file_to_api(filename, file, uniqueid, extra=""):

    # Ottengo il documento id per poi fare l'update necessario
    post_data = {'asterisk_recordings_uniqueid': uniqueid, 'asterisk_recordings_filename':filename, 'asterisk_recordings_file': file}
    r = requests.post(url+"create/asterisk_recordings", data=post_data, headers=headers)
    #response = json.loads(r.text) Da errore se non torna un json 
    #print response
    time.sleep(1)

def send_to_api(data):
    post_data = data
    r = requests.post(url+"create/asterisk_log_calls", data=post_data, headers=headers, timeout=20)
    print r.text
    # Check exists registrazione
    filename = data['asterisk_log_calls_uniqueid']+'.wav'
    file = percorso_registrazioni + filename
    if (os.path.isfile(file) == True) and (os.path.getsize(file) > 50) == True:
        print "File registrazione rilevato: "+file
        file_data = open(file, "r").read()
        file_encoded = base64.b64encode(file_data)
        send_file_to_api(filename, file_encoded, data['asterisk_log_calls_uniqueid'])
        
    #response = json.loads(r.text)



print "Start script..."

# Chiedo al server ultimo unique id che ha
post_data = {'limit': 1, 'offset': 0, 'orderby': 'asterisk_log_calls_uniqueid', 'orderdir': 'DESC'}
r = requests.post(url+"search/asterisk_log_calls", data=post_data, headers=headers, timeout=20)
response = json.loads(r.text)
last_unique_id = response['data'][0]['asterisk_log_calls_uniqueid']
print last_unique_id


# Prendo tutti i record con l unique id piu alto di quello che ha attualmente il server
rows = query("s", "SELECT * FROM `cdr` WHERE `call_direction` IS NOT NULL AND `uniqueid` >= "+last_unique_id)

post = {}

for row in rows:
    post['asterisk_log_calls_calldate'] = row['calldate']
    post['asterisk_log_calls_callee_name'] = row['callee_name']
    post['asterisk_log_calls_callee_num'] = row['callee_num']
    post['asterisk_log_calls_dest_name'] = row['dest_name']
    post['asterisk_log_calls_dest_num'] = row['dest_num']
    post['asterisk_log_calls_call_direction'] = row['call_direction']
    post['asterisk_log_calls_clid'] = row['clid']
    post['asterisk_log_calls_src'] = row['src']
    post['asterisk_log_calls_dst'] = row['dst']
    post['asterisk_log_calls_dcontext'] = row['dcontext']
    post['asterisk_log_calls_channel'] = row['channel']
    post['asterisk_log_calls_dstchannel'] = row['dstchannel']
    post['asterisk_log_calls_lastapp'] = row['lastapp']
    post['asterisk_log_calls_lastdata'] = row['lastdata']
    post['asterisk_log_calls_duration'] = row['duration']
    post['asterisk_log_calls_billsec'] = row['billsec']
    post['asterisk_log_calls_disposition'] = row['disposition']
    post['asterisk_log_calls_amaflags'] = row['amaflags']
    post['asterisk_log_calls_accountcode'] = row['accountcode']
    post['asterisk_log_calls_uniqueid'] = row['uniqueid']
    post['asterisk_log_calls_userfield'] = row['userfield']
    post['asterisk_log_calls_switchboard'] = 3; #corrisponde al moltibox
    send_to_api(post)
    time.sleep(1)
    print "Post sent..."


#pprint(rows)

