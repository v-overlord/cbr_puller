 ## The Central Bank of Russia's rate retriever
 
 ### Description:

This utility is used to display rates from the CBR in various formats.  
It has the ability to publish them to a message broker (using a Kafka instance for now).  
Additionally, it utilizes a cache (currently using a Redis instance).

 ### Install

Setting up the instance is a breeze with Docker.  
I've got you covered with some handy bash scripts that you can find below.

First, clone the repo:  

```bash
git clone https://github.com/v-overlord/cbr_puller
```

Next, you can edit the `env.example` file if needed.  
It contains configurations for both the Redis and Kafka instances.  
However, for the demo purposes, I have already installed them and no editing is required by default.

And then, run:

```bash
exec/docker.sh init
```

The script takes the `env.example` file, sets up the necessary variables, and builds the Docker compose file, with the images and hookers.

What's all.

### Bash Scripts

Although I'm not an admin, I have written some useful scripts that might be helpful:
 * `exec/docker.sh` is used to manage the Docker containers. It accepts various commands such as `init, install, 1 [up], 0 [down]`.  
 * `exec/exec.sh` allows you to run commands within the CLI container. (For example, `exec/exec.sh php -i` will display the php info).  
 * `exec/cli.sh` is similar to the `exec.sh`, but it hides all docker-specific commands and provides a utility-like interface (`exec/cli.sh pull USD...`).
 * `exec/kafka.sh` offers a few commands specifically for the Kafka instance to make things easier. You can use commands `show_topic|list` for convenience.

### How do I use it?

To start the containers, use the command `exec/docker.sh 1`. To stop the containers, use the command `exec/docker.sh 0`.

You can see the available options and capabilities by using the help command `exec/cli.sh pull --help`:

```text
Description:
  This command fetches the cross rate for the specified currency from the CBR, using the base currency (default RUR).

Usage:
  pull [options] [--] <currency> <date>

Arguments:
  currency                             What currency are you requesting a rate for?
  date                                 A date for which you would like to fetch the rates information [yyyy-mm-dd]

Options:
  -b, --base-currency[=BASE-CURRENCY]  What is the base currency for which you would like to obtain a cross rate? [default: "RUR"]
  -d, --renderer[=RENDERER]            Here, configure the renderer that will produce the output format, which can be either 'cli' or 'json'. [default: "cli"]
  -c, --no-cache                       Enabling this option will prevent the use of the cache and force the retrieval of the currency rate from the remote source.
  -r, --reset-cache                    By selecting this option, the cache will be reset.
  -e, --exact-date                     You can use this option to fetch the exchange rate specifically for an exact date, with the default being to select the latest available date.
  -h, --help                           Display help for the given command. When no command is given display help for the list command
  -q, --quiet                          Do not output any message
  -V, --version                        Display this application version
      --ansi|--no-ansi                 Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                 Do not ask any interactive question
  -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

You can also control the verbosity of the output and logger. If you increase the level to "NORMAL", the debug messages will not be displayed.

---

If you want to get the RUR/USD exchange rates for 2023-09-09, in JSON format with cache resetting and specifically for this date, you can run the following command:  
```bash
exec/cli.sh pull USD 2023-09-09 -d json -cre
```

```text
 > exec/cli.sh pull USD 2023-09-09 -d json -cre
[logDate] CbrPullerLogger->DEBUG: I'm attempting to retrieve the rate for RUR/USD...
[logDate] CbrPullerLogger->DEBUG: Redis cache: The cache has been completely emptied!
[logDate] CbrPullerLogger->INFO: The cache is not in use, retrieving data directly from the source...
[logDate] CbrPullerLogger->DEBUG: Redis cache: Cache hit for the key 'cbrPuller_info_for_USD'!
[logDate] CbrPullerLogger->INFO: Request: "GET https://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=13/03/2023&date_req2=09/09/2023&VAL_NM_RQ=R01235"
[logDate] CbrPullerLogger->INFO: Response: "200 https://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=13/03/2023&date_req2=09/09/2023&VAL_NM_RQ=R01235"
[logDate] CbrPullerLogger->DEBUG: Redis cache: Set the key 'cbrPuller_USD/RUR_history' in the cache!
[logDate] CbrPullerLogger->DEBUG: Redis cache: Set the key 'cbrPuller_RUR/USD_history' in the cache!
[logDate] CbrPullerLogger->INFO: This is the result of your request:
[
    {
        "date": "09\/09\/2023",
        "pair": "RUR\/USD",
        "rate": 0.01021,
        "rateDifference": 3.0e-5,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 97.9241,
        "swappedPairRateDifference": -0.272
    },
    {
        "date": "08\/09\/2023",
        "pair": "RUR\/USD",
        "rate": 0.01018,
        "rateDifference": 0,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 98.1961,
        "swappedPairRateDifference": 0
    }
]
```

To retrieve the rates for EUR/USD on 2023-09-10, in CLI format, simply execute the following command:
```bash
exec/cli.sh pull USD 2023-09-10 -d json
```

```text
 > exec/cli.sh USD 2023-09-10 -d json
[logDate] CbrPullerLogger->DEBUG: I'm attempting to retrieve the rate for RUR/USD...
[logDate] CbrPullerLogger->DEBUG: Redis cache: Cache hit for the key 'cbrPuller_RUR/USD_history'!
[logDate] CbrPullerLogger->DEBUG: Redis cache: Cache hit for the key 'cbrPuller_info_for_USD'!
[logDate] CbrPullerLogger->INFO: Request: "GET https://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=14/03/2023&date_req2=10/09/2023&VAL_NM_RQ=R01235"
[logDate] CbrPullerLogger->INFO: Response: "200 https://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=14/03/2023&date_req2=10/09/2023&VAL_NM_RQ=R01235"
[logDate] CbrPullerLogger->DEBUG: Redis cache: Set the key 'cbrPuller_USD/RUR_history' in the cache!
[logDate] CbrPullerLogger->DEBUG: Redis cache: Set the key 'cbrPuller_RUR/USD_history' in the cache!
[logDate] CbrPullerLogger->INFO: On the requested date the price not found, there is the closest to the date that the bank have:
[logDate] CbrPullerLogger->INFO: This is the result of your request:
[
    {
        "date": "09\/09\/2023",
        "pair": "RUR\/USD",
        "rate": 0.01021,
        "rateDifference": 3.0e-5,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 97.9241,
        "swappedPairRateDifference": -0.272
    },
    {
        "date": "08\/09\/2023",
        "pair": "RUR\/USD",
        "rate": 0.01018,
        "rateDifference": 0,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 98.1961,
        "swappedPairRateDifference": 0
    }
]
```

Have fun!

### Publisher

In addition to its regular functionality, this utility also has the capability to publish the rates to a broker topic, such as a Kafka instance.  
The API remains the same, but instead of displaying the output, it sends the rates directly to the configured topic.  
You can view the results by executing the `exec/kafka.sh show_topic` command.  

If you need to constantly receive updated data from CBR, you can set up a cron job for that purpose.

### Tests

Run `exec/exec.sh composer run test`.

### FAQ:
 Q: I'm encountering connection issues while configuring Docker.  
 A: It's possible that you're experiencing these problems due to connection resets by Roskomnadzor.  
 You can try using a VPN to see if it helps resolve the issue.  
 Please note that this is not an explicit instruction to use a VPN or an endorsement of any specific VPN service, as I don't want to get into trouble with Roskomnadzor... for now.

 Q: I'm having trouble running the scripts.  
 A: `chmod u+x exec/...` should help.

 Q: I'd like to debug.  
 A: To make the changes, you need to modify the files `./data/php/conf.d/docker-php-ext-xdebug.ini` and `./exec/php/Dockerfile`, and then rebuild the containers.