# AWS Signed Cloudfront Download for Wordpress

This wordpress plugin will enable a shortcode to allow signed URL to access 
private content behind an Amazon CloudFront distribution.

Create a key pair and configure the CloudFront distribution appropriately 
as well as in this plugin and then you can wrap the base URL with tags 
[wp-cloudfront-sign]my-url[/wp-cloudfront-sign].

The download functionality will only work for configured domains - a list of
which (comma separated) can be included in the options.

Full details of the generation of the keypair and configuration of AWS can 
be found in the 
[AWS documentation](http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/PrivateContent.html)

This plugin does not manage the process of getting Media Assets on to Amazon
S3 or modify the Domain name for AWS based media - nor will it work for 
streaming media such as HLS video as all of the subassets need also to 
be signed and not just the manifest file.
