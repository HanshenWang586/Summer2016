<?php
class VideoController {

	public function xml() {

		if ($_GET['video_id'] == 1) {
			echo "<?xml version=\"1.0\"?>
<video>
<src>1.flv</src>
<title>UFO Geezer</title>
<cue_points>

<cue>
<time>0.5</time>
<text>I am a professor of physics at Yunnan University and I am very interested in physics and astronomy.</text>
</cue>

<cue>
<time>5.5</time>
<text>I've had this job since 1978, we became very interested in UFOs when the reports of UFOs in China started.</text>
</cue>

<cue>
<time>13.9</time>
<text>This association was established in 1985.</text>
</cue>

<cue>
<time>18.133333333333</time>
<text>People say, what is a UFO?</text>
</cue>

<cue>
<time>21.9</time>
<text>UFO, because we Chinese translated the term from English</text>
</cue>

<cue>
<time>26.466666666667</time>
<text>in actuality it means \"unidentified flying object.\" Even now I don't understand what the thing is,</text>
</cue>

<cue>
<time>32.533333333333</time>
<text>but I am convinced that it must be extraterrestrial life.</text>
</cue>

<cue>
<time>35.533333333333</time>
<text>I am convinced, because I am a physicist and an astronomer. I am convinced that extraterrestrials exist.</text>
</cue>

<cue>
<time>42.3</time>
<text>These were discovered in Kunming, right here in Kunming,</text>
</cue>

<cue>
<time>47.033333333333</time>
<text>right at our house! My wife and son discovered it together.</text>
</cue>

<cue>
<time>52.233333333333</time>
<text>I see this thing flying around that is really bright, but at first I thought it was an airplane</text>
</cue>

<cue>
<time>55.5</time>
<text>so I kept watching TV but this thing kept flying closer and closer and so quickly!</text>
</cue>

<cue>
<time>64.1</time>
<text>I went to look for him, and told him \"Quick, quick, come here and look! What is that? Is that a UFO?\"</text>
</cue>

<cue>
<time>69.966666666667</time>
<text>He comes running and sees it too! He also thinks it is strange, then my son says to me \"Hey, go grab the camera!\"</text>
</cue>

<cue>
<time>78.366666666667</time>
<text>So I got the camera and gave it to him and he took pictures of it. He took pictures but the thing was so fast!</text>
</cue>

<cue>
<time>87.366666666667</time>
<text>This is in Texas, Oklahoma, Arizona, New York, Oregon, Wisconsin.</text>
</cue>

<cue>
<time>97.4</time>
<text>There are so many in the United States! About 400 or 500.</text>
</cue>

<cue>
<time>101.8</time>
<text>When they picked up cameras, that is to say when Americans had cameras</text>
</cue>

<cue>
<time>105.46666666667</time>
<text>many Chinese people might not have even seen a car yet.</text>
</cue>

<cue>
<time>108.6</time>
<text>So, awareness of popular science and the material conditions were less developed in China than the U.S.</text>
</cue>

<cue>
<time>115.96666666667</time>
<text>There were reports, but due to China's special circumstances, it wasn't exactly open, reports weren't published.</text>
</cue>

<cue>
<time>125.3</time>
<text>People said \"what might these things be?\"</text>
</cue>

<cue>
<time>128.16666666667</time>
<text>But they couldn't casually speak about this. It's not like that now, where people rely on science to pursue these questions and discuss them.</text>
</cue>

<cue>
<time>136.36666666667</time>
<text>Scientific experts say that we can't use conventional scientific practices or astronomical observations</text>
</cue>

<cue>
<time>144.83333333333</time>
<text>and theories to explain this sort of thing. We need people to continue forward with their exploration and research.</text>
</cue>

<cue>
<time>154.23333333333</time>
<text>Also, we put out a book. It's the first book out in China about UFOs. It was published in 2007 by the Yunnan University Press.</text>
</cue>

<cue>
<time>167.5</time>
<text>We want to use this opportunity in part to popularize and teach about astronomy, but also to popularize awareness of UFOs.</text>
</cue>

<cue>
<time>174.86666666667</time>
<text>So this forum is at this location in 2009, a forum in Kunming on extraterrestrial life and UFOs.</text>
</cue>

<cue>
<time>181.46666666667</time>
<text>Yunnan has some of the most sightings. The sightings in China are mostly in Yunnan, Xinjiang, and Heilongjiang.</text>
</cue>

<cue>
<time>190.06666666667</time>
<text>The history of UFOs in Kunming is great, sightings are commonplace.</text>
</cue>

<cue>
<time>197.26666666667</time>
<text>I hope that we can make friends with extraterrestrials.</text>
</cue>

</cue_points>
</video>";
		}
		else if ($_GET['video_id'] == 2) {
			echo "<?xml version=\"1.0\"?>
<video>
<src>2.flv</src>
<title>Jesse Rodenbiker</title>
<cue_points>
<cue>
</cue>
</cue_points>
</video>";
		}
		else if ($_GET['video_id'] == 3) {
			echo "<?xml version=\"1.0\"?>
<video>
<src>3.flv</src>
<title>Chen Jianxuan</title>
<cue_points>
<cue>
</cue>
</cue_points>
</video>";
		}
		else if ($_GET['video_id'] == 5) {
			echo "<?xml version=\"1.0\"?>
<video>
<src>5.flv</src>
<title>Nujiang Zipline</title>
<cue_points>
<cue></cue>
</cue_points>
</video>";
		}

	}
}
?>