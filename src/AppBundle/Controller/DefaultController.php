<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Madcoda\Youtube\Youtube;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
    {

        if ($request->get("submit") == "1") {
            $platformList = $request->get("platform");
            $streamList   = $request->get("stream");

            $streamString = "";
            foreach ($streamList AS $index => $stream) {
                if (!empty($stream)) {

                    $tmpStreamString = "";

                    switch ($platformList[$index]) {
                        case "youtube":
                            $tmpStreamString .= "yt:";
                            break;
                        case "twitch":
                            $tmpStreamString .= "tw:";
                            break;
                    }

                    $tmpStreamString .= urlencode($stream);
                    $streamString    .= $tmpStreamString . "/";
                }
            }

            if (strlen($streamString) > 0) {
                $streamString = substr($streamString, 0, -1);
                return $this->redirectToRoute("stream_view", ["streams" => $streamString]);
            }

        }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/{streams}/", name="stream_view", requirements={"streams"=".+"})
     */
    public function streamViewIndex($streams)
    {
        $apiKey  = $this->container->getParameter('youtube_apikey');
        $youtube = new Youtube(['key' => $apiKey]);

        $streamList      = [];
        $streamParameter = array_unique(explode("/", $streams));
        foreach ($streamParameter AS $streamanem) {
            if ($streamanem != "") {

                $explodeStream = explode(":", $streamanem);
                // Youtube
                if ($explodeStream[0] == "yt") {
                    $apiData = $youtube->getChannelByName($explodeStream[1]);
                    if (isset($apiData->id)) {
                        $channelId = $apiData->id;
                    } else {
                        $apiData = $youtube->search($explodeStream[1]);
                        if (count($apiData) > 0){
                            $channelId = $apiData[0]->id->channelId;
                        }
                    }

                    if (isset($channelId)) {
                        $streams = $youtube->searchChannelLiveStream($explodeStream[1], $channelId);
                        if (isset($streams[0]) && isset($streams[0]->id) && isset($streams[0]->id->videoId)) {
                            $streamList[] = [
                                "source"  => "youtube",
                                "row"     => $streams[0]->snippet->channelTitle,
                                "channel" => $streams[0]->snippet->channelId,
                                "stream"  => "https://www.youtube.com/embed/live_stream?channel=" . $streams[0]->snippet->channelId,
                                "chat"    => "https://www.youtube.com/live_chat?embed_domain=" . $_SERVER['SERVER_NAME'] . "&v=" . $streams[0]->id->videoId,
                            ];
                        }
                    }
                } // Twitch
                elseif ($explodeStream[0] == "tw") {
                    $streamList[] = [
                        "source"  => "twitch",
                        "row"     => $explodeStream[1],
                        "channel" => $explodeStream[1],
                        "stream"  => "http://twitch.tv/" . $explodeStream[1] . "/embed?muted=true",
                        "chat"    => "https://www.twitch.tv/" . $explodeStream[1] . "/chat",
                    ];
                }
            }
        }

        if (count($streamList) > 0) {
            return $this->render(
                "default/stream.html.twig", [
                "streamData" => $streamList,
            ]);
        } else {
            return $this->redirectToRoute("home");
        }
    }
}
