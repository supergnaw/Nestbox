# Lorikeet

Image upload processing and index management.

## Settings

| Setting                         | Description                                                   | Default  |
|:--------------------------------|---------------------------------------------------------------|:--------:|
| lorikeetImageSaveDirectory      | directory where processed images are stored                   |  `"."`   | 
| lorikeetImageThumbnailDirectory | directory where thumbnails are stored                         |  `"."`   | 
| lorikeetKeepAspectRatio         | if image should keep aspect ratio during processing           |  `true`  | 
| lorikeetMaxWidth                | maximum image width in pixels, 0 for original size            |   `0`    | 
| lorikeetMaxHeight               | maximum image height in pixels, 0 for original size           |   `0`    | 
| lorikeetMaxFilesizeMb           | maximum filesize in megabytes                                 |   `2`    | 
| lorikeetAllowBmp                | allow **.bmp** file uploads                                   |  `true`  | 
| lorikeetAllowGif                | allow **.gif** file uploads                                   |  `true`  | 
| lorikeetAllowJpg                | allow **.jpg** file uploads                                   |  `true`  | 
| lorikeetAllowPng                | allow **.png** file uploads                                   |  `true`  | 
| lorikeetAllowWebp               | allow **.webp** file uploads                                  |  `true`  | 
| lorikeetConvertToFiletype       | convert uploaded images to this type, blank for no conversion | `"webp"` | 
| lorikeetVirusTotalApiKey        | api key for VirusTotal to enable malicious hash checking      |   `""`   | 