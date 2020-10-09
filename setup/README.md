# Automatické testy

## Motivace

K čemu používat testy? Zajímavá otázka, kterou si klade snad
každý před tím, než začne testy psát (a také využívat). Odpověď
není zase tak složitá. Testy jednoduše zaručují to, že je Tvůj
kód strojově kontrolován. Z toho plyne několik výhod, mimo jiné
to, že se ti nemůže stát, že 5 minut před odevzdáním zjistíš,
že ti po poslední úpravě polovina projektu nefunguje. Testy Ti
to sdělí hned poté, co danou změnu uděláš, takže to můžeš začít
ihned řešit.

Proč testy automatizovat? No, jsme jen lidi, takže velmi často
zapomínáne na procedury, které máme vykonat. Pokud si nevzpomeneš
na to, že máš spustit testy, je to stejné, jako bys ani žádné
neměl napsané. Smůla, že? Takže automatizovat!

Díky testům jednoduše budeš vědět, kd můžeš projekt odevzdat.
Jakmile budou všechny testy úspěšné, není již co řešit a stačí
jen zabalit zdrojáky a poslat je ke kontrole. Nic víc, nic míň.

## Požadavky

Aby vůbec bylo možné automaticky testovat Tvoje zdrojové kódy,
je třeba, abys používat Git a měl vzdálený repozitář na Gitlabu.

Dále je třeba provést pár kroků, které jsou dále podrobně rozepsané.
Pokud s tím budeš mít nějaký problém, piš na Discord, s Vojtou
se pokusíme ti nějak pomoct.

## Slíbený návod

Dále se již počítá s tím, že používáš Git a máš ho provázaný
s repozitářem od Gitlabu. Pokud si nevíš rady ani s tímto,
zkus googlit, případně se ozvi na Discordu.

1. V root adresáři svého projektu (bude tam nejspíše také 
soubor se zdrojovými kódy projektu) vytvoř soubor ```.gitlab-ci.yml```.
2. Zobraz si [vzorový obsah souboru .gitlab-ci.yml]
(https://gitlab.com/ceskyDJ/izp-sheet/-/blob/master/setup/.gitlab-ci.yml).
3. Toto nastavení by mělo být univerzální, můžeš ho tedy zkopírovat
a vložit do nově vytvořeného ```.gitlab-ci.yml``` souboru ve tvém
projektu.
4. Nyní jen stačí commitnout změny a poslat je do repozitáře
na Gitlabu.
```shell script
git add .gitlab-ci.yml
git commit -m "Added Gitlab config file for pipelines tests"
git push origin master
```
5. Hotovo - v Gitlabu se po nahrání commitu již automaticky spustí
testy. Jejich výsledek je napsaný u každého commitu.