import tweepy

# Unique code from Twitter
access_token = "2473973030-dT9wJ0O4EedtLjdwr17JmDlsriHZVFK9rKofDs7"
access_token_secret = "XYfbm1JgHP4MG0yW7H4NmDKHuGjJ6M3R7iiaXICIasWIW"
consumer_key = "4S5UioS8r18Dg9q5KeFjRWssx"
consumer_secret = "	IaqTNMBtYWC9UUijpgnBX3DJoA1JlCZFckVEIysonM3hDCsfel"

# Boilerplate code here
auth = tweepy.OAuthHandler(consumer_key,consumer_secret)
auth.set_access_token(access_token,access_token_secret)

api = tweepy.API(auth)
#Now we can Create Tweets, Delete Tweets, and Find Twitter Users

public_tweets = api.search('UMSI')


for tweet in public_tweets:
	print(tweet.text)
	
#Learn more about Search
#https://dev.twitter.com/rest/public/search

